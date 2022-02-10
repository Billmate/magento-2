<?php

namespace Billmate\NwtBillmateCheckout\Controller\Processing;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\CredentialsDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Model\Service\ReturnRequestData;
use Billmate\NwtBillmateCheckout\Model\QuoteValidationRules\MatchesPayment;
use InvalidArgumentException;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\App\RequestInterface;

/**
 * Used as accepturl for billmate payments.
 * Creates orders for payment methods that don't use the checkout_success js event,
 * like Card and Bank Transfer payments
 */
class Confirmorder extends ProcessingAbstract
{
    /**
     * Stores result of request verification
     *
     * @var DataObject
     */
    private $verifyResult;

    public function __construct(
        OrderUtil $orderUtil,
        ControllerUtil $util,
        DataUtil $dataUtil,
        ReturnRequestData $returnRequestData
    ) {
        parent::__construct($util, $dataUtil, $orderUtil, $returnRequestData);
        $this->verifyResult = $dataUtil->createDataObject(['verified' => false]);
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

        $requestContent = $this->returnRequestData->getRequestContent();
        $this->dataUtil->setContextPaymentNumber($requestContent->getDataByPath('data/number'));
        $this->verifyRequest();
        if (!$this->handleVerifyResult()) {
            return $this->redirectToCart();
        }

        try {
            $quote = $this->getQuoteForOrder();
        } catch (NoSuchEntityException $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart($quote);
        }

        $invoiceNumber = $requestContent->getDataByPath('data/number');

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(ResponseValidator::KEY_INVOICE_NUMBER, $invoiceNumber);
        $payment->setAdditionalInformation(
            CredentialsDataBuilder::KEY_BILLMATE_TEST_MODE,
            $this->dataUtil->getConfig()->getTestMode($quote->getStoreId())
        );
        $payment->setAdditionalInformation(MatchesPayment::KEY_VALIDATE_PAYMENT_MATCH, 1);
        $this->orderUtil->getQuoteRepository()->save($quote);

        try {
            $this->orderUtil->placeOrder($quote->getId());
        } catch (\Exception $e) {
            $this->addExceptionMessage($e);
            return $this->redirectToCart($quote);
        }

        return $this->util->redirect('billmate/checkout/success');
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

        $requestContent = $this->returnRequestData->getRequestContent();
        $this->dataUtil->setContextPaymentNumber($requestContent->getDataByPath('data/number'));
        if (!$this->dataUtil->verifyHash($requestContent)) {
            $this->addErrorMessage('Invalid hash in request from Billmate');
            return false;
        }

        return true;
    }

    /**
     * Redirect shorthand
     *
     * @param Quote|null $quote
     * @return Redirect
     */
    private function redirectToCart(?Quote $quote = null): Redirect
    {
        $params = [];
        if ($quote instanceof Quote) {
            $params = ['reserved' => $quote->getReservedOrderId()];
        }
        return $this->util->redirect('checkout/cart', $params);
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
     * Verifies request content and stores result in self::verifyResult
     *
     * @return void
     */
    private function verifyRequest(): void
    {
        $errors = 0;
        // Error = order already exists for this increment ID
        $requestContent = $this->returnRequestData->getRequestContent();
        $incrementId = $requestContent->getDataByPath('data/orderid');
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
         * Need to get quote by reserved order ID
         */
        $requestContent = $this->returnRequestData->getRequestContent();
        $orderId = $requestContent->getDataByPath('data/orderid');
        $quote = $this->orderUtil->getQuoteByReservedOrderId($orderId);
        if (null === $quote) {
            throw new NoSuchEntityException(
                __(
                    sprintf('Could not find quote with matching reserved order id (%s)', $orderId)
                )
            );
        }
        /** @var Quote $quote */
        return $quote;
    }
}
