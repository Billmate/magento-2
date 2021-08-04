<?php

namespace Billmate\NwtBillmateCheckout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class Checkout implements ArgumentInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
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
}
