<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\CredentialsDataBuilder;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\PaymentDataBuilder;

/**
 * Controller for checkout_success event.
 * Used by Invoice and Swish payments to create order instead of Confirmorder controller
 */
class SuccessEvent implements HttpPostActionInterface
{
    private ControllerUtil $util;

    private OrderUtil $orderUtil;

    private DataUtil $dataUtil;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
    }

    public function execute()
    {
        $result = $this->util->jsonResult();
        $request = $this->util->getRequest();
        if (!$request->isAjax() || !$request->isPost() || !$this->util->validateFormKey()) {
            return $this->util->forwardNoRoute();
        }

        try {
            $quote = $this->util->getCheckoutSession()->getQuote();
            $payment = $quote->getPayment();
            $payment->setAdditionalInformation(
                CredentialsDataBuilder::KEY_BILLMATE_TEST_MODE,
                $this->dataUtil->getConfig()->getTestMode()
            );
    
            $this->dataUtil->setContextPaymentNumber(
                $quote->getPayment()->getAdditionalInformation(PaymentDataBuilder::PAYMENT_NUMBER)
            );

            $this->orderUtil->getQuoteRepository()->save($quote);
            $this->orderUtil->placeOrder($quote->getId());
        } catch (\Exception $e) {
            $this->dataUtil->logErrorMessage('Failed to place order! Exception: ' . $e->getMessage());
            return $result->setData(['success' => false]);
        }
        return $result->setData(['success' => true]);
    }
}
