<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\CredentialsDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\AbstractResult;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Used as accepturl for billmate payments
 */
class Confirmorder implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var DataUtil
     */
    private $dataUtil;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * Stores result of request verification
     *
     * @var DataObject
     */
    private DataObject $verifyResult;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil,
        SubscriptionManager $subscriptionManager
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
        $this->subscriptionManager = $subscriptionManager;
        $this->verifyResult = $dataUtil->createDataObject(['verified' => false]);
    }

    /**
     * Verifies return data from Billmate and places order
     *
     * @return AbstractResult
     */
    public function execute()
    {
        $checkoutSession = $this->util->getCheckoutSession();
        $this->dataUtil->setContextPaymentNumber($checkoutSession->getBillmatePaymentNumber());
        try {
            $content = $this->extractContent();
        } catch (\Exception $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

        $this->verifyRequest($content);
        if (!$this->handleVerifyResult()) {
            return $this->redirectToCart();
        }

        try {
            $quote = $this->getQuoteForOrder();
        } catch (NoSuchEntityException $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

        $checkoutSession->clearQuote();
        $invoiceNumber = $content->getData('data')->getNumber();

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(ResponseValidator::KEY_INVOICE_NUMBER, $invoiceNumber);
        $payment->setAdditionalInformation(
            CredentialsDataBuilder::KEY_BILLMATE_TEST_MODE,
            $this->dataUtil->getConfig()->getTestMode()
        );
        $this->orderUtil->getQuoteRepository()->save($quote);

        try {
           $this->orderUtil->placeOrder($quote->getId());
        } catch (\Exception $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

        if ($checkoutSession->getBillmateSubscribeNewsletter()) {
            $this->subscriptionManager->subscribe($quote->getCustomerEmail(), $quote->getStore()->getWebsiteId());
        }

        $checkoutSession->unsBillmatePaymentNumber();
        return $this->util->redirect('billmate/checkout/success');
    }


    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($this->util->getRequest()->getMethod() !== 'POST') {
            return false;
        }

        $checkoutSession = $this->util->getCheckoutSession();
        $this->dataUtil->setContextPaymentNumber($checkoutSession->getBillmatePaymentNumber());
        $content = $this->dataUtil->unserialize($this->util->getRequest()->getContent());
        if (!$this->dataUtil->verifyHash($content)) {
            $this->addErrorMessage('Invalid hash in request from Billmate');
            return false;
        }

        return true;
    }

    /**
     * Redirect shorthand
     *
     * @return Redirect
     */
    private function redirectToCart(): Redirect
    {
        return $this->util->redirect('checkout/cart');
    }

    /**
     * Set error message in message manager
     *
     * @param string $message
     * @return void
     */
    private function addErrorMessage(string $message = null): void
    {
        $message = $message ?? 'Error encountered when placing order. Please contact customer support';
        $this->dataUtil->displayErrorMessage($message);
    }
 
    /**
     * Set exception message in message manager
     *
     * @param \Exception $exception
     * @param string $alternativeText
     * @return void
     */
    private function addExceptionMessage(\Exception $exception, string $alternativeText = null): void
    {
        $this->dataUtil->displayExceptionMessage($exception, $alternativeText);
    }

    /**
     * Get POST request content
     *
     * @return DataObject
     * @throws \InvalidArgumentException
     */
    private function extractContent(): DataObject
    {
        return $this->dataUtil->unserialize($this->util->getRequest()->getContent());
    }

    /**
     * Verifies request content and stores result in self::verifyResult
     *
     * @param DataObject $requestContent
     * @throws CreateOrderException
     * @return void
     */
    private function verifyRequest($requestContent): void
    {
        $errors = 0;
        // Error = order already exists for this increment ID
        $incrementId = $requestContent->getData('data')->getOrderid();
        $errors |= ($this->orderUtil->loadOrderByIncrementId($incrementId)->getId());

        // Error = Missing quote to process
        $quoteId = $this->util->getCheckoutSession()->getBillmateQuoteId();
        $errors |= (!$quoteId) ? 2 : 0;

        if ($errors > 0) {
            $messages = [];
            if ($errors & 1) {
                $messages[] = sprintf('Order with this increment ID (%s) already exists in Magento', $incrementId);
            }

            if ($errors & 2) {
                $messages[] = 'No quote ID found in the session';
            }

            $this->verifyResult->setMessages($messages);
            return;
        }

        $this->verifyResult->setVerified(true);
    }

    /**
     * Check verification result, and set appropriate customer-facing error message(s)
     *
     * @return boolean
     */
    private function handleVerifyResult(): bool
    {
        if ($this->verifyResult->getVerified()) {
            return true;
        }

        $config = $this->dataUtil->getConfig();
        $debugMessages = $this->verifyResult->getMessages();
        $productionMessage = $config->getDefaultErrorMessage();

        // Log the errors
        foreach ($debugMessages as $message) {
            $this->dataUtil->logErrorMessage($message);
        }

        // Show all messages if in test mode
        if ($config->getTestMode()) {
            foreach ($debugMessages as $message) {
                $this->addErrorMessage($message);
            }

            return false;
        }

        // If in production mode, show generic error message tailored for customer
        $this->addErrorMessage($productionMessage);
        return false;
    }

    /**
     * Gets the correct quote for order placement
     *
     * @return Quote
     * @throws NoSuchEntityException
     */
    private function getQuoteForOrder(): Quote
    {
        $checkoutSession = $this->util->getCheckoutSession();
        $billmateQuoteId = $checkoutSession->getBillmateQuoteId();
        $quote = $checkoutSession->getQuote();

        /**
         * Since we set the quote as inactive before checkout reaches this point,
         * it is theorerically possible that the customer has started a new checkout session
         * before completing the payment.
         *
         * Very unlikely to happen, but if it does, it will be a major headache.
         * So we handle it by loading the quote from the stored Id if it differs from the current active quote ID.
         */
        if ($quote->getId() !== $billmateQuoteId) {
            $quote = $this->orderUtil->getQuoteRepository()->get($billmateQuoteId);
        }

        return $quote;
    }
}
