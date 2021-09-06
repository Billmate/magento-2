<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;

class SavePaymentMethod implements HttpPostActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepo;

    public function __construct(
        ControllerUtil $util,
        Session $checkoutSession,
        QuoteRepositoryInterface $quoteRepo
    ) {
        $this->util = $util;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepo = $quoteRepo;
    }

    public function execute()
    {
        if (!$this->util->isAjax() || !$this->util->validateFormKey()) {
            return $this->util->forwardNoRoute();
        }

        $methodId = $this->util->getRequest()->getParam('methodId');
        $methodDescription = Config::PAYMENT_METHOD_MAPPING[$methodId];

        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('billmate_method_id', $methodId);
        $payment->setAdditionalInformation('billmate_method_description', $methodDescription);
        $this->quoteRepo->save($quote);

        return $this->util->jsonResult(['success' => true]);
    }
}
