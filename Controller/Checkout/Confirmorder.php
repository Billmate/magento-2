<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\CredentialsDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use InvalidArgumentException;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Used as accepturl for billmate payments.
 * Creates orders for payment methods that don't use the checkout_success js event,
 * like Card and Bank Transfer payments
 */
class Confirmorder implements HttpPostActionInterface, CsrfAwareActionInterface
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
     * Stores result of request verification
     *
     * @var DataObject
     */
    private DataObject $verifyResult;

    /**
     * Request content as a DataObject
     *
     * @var DataObject
     */
    private DataObject $requestContent;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
        $this->verifyResult = $dataUtil->createDataObject(['verified' => false]);
        $this->requestContent = $dataUtil->createDataObject();
    }

    /**
     * Verifies return data from Billmate and places order
     *
     * @return AbstractResult
     */
    public function execute()
    {
        try {
            $this->extractContent();
        } catch (\Exception $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

        $this->dataUtil->setContextPaymentNumber($this->requestContent->getDataByPath('data/number'));
        $this->verifyRequest();
        if (!$this->handleVerifyResult()) {
            return $this->redirectToCart();
        }

        try {
            $quote = $this->getQuoteForOrder();
        } catch (NoSuchEntityException $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

        $invoiceNumber = $this->requestContent->getDataByPath('data/number');

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(ResponseValidator::KEY_INVOICE_NUMBER, $invoiceNumber);
        $payment->setAdditionalInformation(
            CredentialsDataBuilder::KEY_BILLMATE_TEST_MODE,
            $this->dataUtil->getConfig()->getTestMode($quote->getStoreId())
        );
        $this->orderUtil->getQuoteRepository()->save($quote);

        try {
           $this->orderUtil->placeOrder($quote->getId());
        } catch (\Exception $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart();
        }

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

        try {
            $this->extractContent();
        } catch (InvalidArgumentException $e) {
            $this->addExceptionMessage($e);
            return false;
        }

        $this->dataUtil->setContextPaymentNumber($this->requestContent->getDataByPath('data/number'));
        if (!$this->dataUtil->verifyHash($this->requestContent)) {
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
     * Set request content property
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function extractContent(): void
    {
        $credentials = $this->dataUtil->unserialize($this->util->getRequest()->getParam('credentials', ''));
        $data = $this->dataUtil->unserialize($this->util->getRequest()->getParam('data', ''));
        $this->requestContent = $this->dataUtil->createDataObject(['credentials' => $credentials, 'data' => $data]);
    }

    /**
     * Verifies request content and stores result in self::verifyResult
     *
     * @return void
     */
    private function verifyRequest(): void
    {
        $errors = 0;
        // Error = order already exists for this increment ID
        $incrementId = $this->requestContent->getData('data')->getOrderid();
        $errors |= ($this->orderUtil->loadOrderByIncrementId($incrementId)->getId());

        if ($errors > 0) {
            $messages = [];
            if ($errors & 1) {
                $messages[] = sprintf('Order with this increment ID (%s) already exists in Magento', $incrementId);
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
        /**
         * Since we set the session quote as inactive before checkout reaches this point,
         * it is theorerically possible that the customer has started a new checkout session
         * before completing the payment.
         *
         * Very unlikely to happen, but if it does, it will be a major headache.
         * So we handle it by loading the quote by the reserved order ID
         */
        $orderId = $this->requestContent->getDataByPath('data/orderid');
        $quote = $this->orderUtil->getQuoteByReservedOrderId($orderId);
        if (null === $quote) {
            throw new NoSuchEntityException(
                __(
                    sprintf('Could not find quote with matching reserved order id (%s)', $orderId)
                )
            );
        }
        return $quote;
    }
}
