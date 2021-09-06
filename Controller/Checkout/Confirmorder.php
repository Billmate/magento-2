<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\CredentialsDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Controller\AbstractResult;

/**
 * Used as accepturl for billmate payments
 */
class Confirmorder implements HttpGetActionInterface
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
     * @var MessageManagerInterface
     */
    private $messageManager;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil,
        MessageManagerInterface $messageManager
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
        $this->messageManager = $messageManager;
    }

    /**
     * Verifies return data from Billmate and places order
     *
     * @return AbstractResult
     */
    public function execute()
    {
        try {
            $content = $this->extractContent();
            $this->verifyRequest($content);
        } catch (\InvalidArgumentException $e) {
            $this->addErrorMessage();
            return $this->redirectToCart();
        } catch (LocalizedException $e) {
            $this->addErrorMessage($e->getMessage());
            return $this->redirectToCart();
        }

        try {
            $quote = $this->getQuoteForOrder();
        } catch (NoSuchEntityException $e) {
            $this->addErrorMessage();
            return $this->redirectToCart();
        }

        $checkoutSession = $this->util->getCheckoutSession();

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
            //TODO Handle
            throw $e;
        }

        return $this->util->redirect('checkout/onepage/success');
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
        $this->messageManager->addErrorMessage($message);
    }
 
    /**
     * Get request content dependeing on configured return method
     *
     * @return DataObject
     * @throws \InvalidArgumentException
     */
    private function extractContent(): DataObject
    {
        $returnMethod = 'GET'; // TODO change to config;
        if ($returnMethod === 'GET') {
            $params = $this->util->getRequest()->getParams();
            return $this->dataUtil->createDataObject([
                'credentials' => $this->dataUtil->unserialize($params['credentials']),
                'data' =>  $this->dataUtil->unserialize($params['data'])
            ]);
        }

        $this->dataUtil->unserialize($this->util->getRequest()->getContent());
    }

    /**
     * Verify request content. Throws exception if invalid.
     *
     * @param DataObject $requestContent
     * @throws LocalizedException
     * @return void
     */
    private function verifyRequest($requestContent): void
    {
        $errors = 0;

        // Error = hash not valid
        $errors |= !$this->dataUtil->verifyHash($requestContent);

        // Error = order already exists for this increment ID
        $incrementId = $requestContent->getData('data')->getOrderid();
        $errors |= ($this->orderUtil->loadOrderByIncrementId($incrementId)->getId()) ? 2 : 0;

        // Error = Missing quote to process
        $quoteId = $this->util->getCheckoutSession()->getData('billmate_quote_id');
        $errors |= (!$quoteId) ? 4 : 0;

        if ($errors > 0) {
            // TODO log specific error messages
            throw new LocalizedException(__('Invalid request from Billmate'));
        }
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
        $billmateQuoteId = $checkoutSession->getData('billmate_quote_id');
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
