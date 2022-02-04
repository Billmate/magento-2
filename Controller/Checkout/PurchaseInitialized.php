<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Quote\Model\QuoteValidator;

/**
 * Sets payment method on quote payment and prepares quote for order completion
 */
class PurchaseInitialized implements HttpPostActionInterface
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
     * @var QuoteValidator
     */
    private $quoteValidator;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        QuoteValidator $quoteValidator
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->quoteValidator = $quoteValidator;
    }

    /**
     * Inactivates the quote and stores the ID to process when payment is completed
     *
     * @return AbstractResult
     */
    public function execute()
    {
        if (!$this->util->getRequest()->isPost() || !$this->util->validateFormKey()) {
            return $this->util->forwardNoRoute();
        }

        $checkoutSession = $this->util->getCheckoutSession();
        $quote = $checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $paymentMethodCode = $this->util->getRequest()->getParam('payment_method_code', false);

        if (!$paymentMethodCode) {
            return $this->util->jsonResult([
                'success' => false,
                'message' => 'Please choose a payment method'
            ]);
        }

        try {
            $this->quoteValidator->validateBeforeSubmit($quote);
            $payment->setAdditionalInformation(
                ResponseValidator::KEY_METHOD_ID,
                $paymentMethodCode
            );
            $payment->setAdditionalInformation(
                ResponseValidator::KEY_METHOD_DESCRIPTION,
                Config::PAYMENT_METHOD_MAPPING[$paymentMethodCode]
            );
        } catch (\Exception $e) {
            return $this->util->jsonResult([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        if ($checkoutSession->getData('billmate_quote_id')) {
            return $this->util->jsonResult(['success' => true]);
        }
        $quote->setIsActive(false);
        $checkoutSession->setData('billmate_quote_id', $quote->getId());
        $this->orderUtil->getQuoteRepository()->save($quote);
        return $this->util->jsonResult(['success' => true]);
    }
}
