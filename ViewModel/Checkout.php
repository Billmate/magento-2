<?php

namespace Billmate\NwtBillmateCheckout\ViewModel;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Helper\Data as PaymentHelper;

class Checkout implements ArgumentInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    private $methodInstance;
    public function __construct(
        CheckoutSession $checkoutSession,
        PaymentHelper $paymentHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get quote ID from session
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->checkoutSession->getQuoteId();
    }

    /**
     * Get logged in customer ID from session
     *
     * @return integer|null
     */
    public function getCustomerId()
    {
        return $this->checkoutSession->getQuote()->getCustomerId();
    }

    /**
     * Get iframe URL
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->checkoutSession->getData('billmate_iframe_url');
    }

    /**
     * Get payment method code
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return Config::METHOD_CODE;
    }

    /**
     * Get payment method title
     *
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        $instance = $this->paymentHelper->getMethodInstance(Config::METHOD_CODE);
        return $instance->getTitle();
    }
}
