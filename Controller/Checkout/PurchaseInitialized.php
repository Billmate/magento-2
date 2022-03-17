<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
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
     * @var QuoteValidator
     */
    private $quoteValidator;

    public function __construct(
        ControllerUtil $util,
        QuoteValidator $quoteValidator
    ) {
        $this->util = $util;
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

        try {
            $this->quoteValidator->validateBeforeSubmit($quote);
        } catch (\Exception $e) {
            return $this->util->jsonResult([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return $this->util->jsonResult(['success' => true]);
    }
}
