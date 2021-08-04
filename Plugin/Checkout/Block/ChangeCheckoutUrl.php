<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Block;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\View\Element\Template;

class ChangeCheckoutUrl
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Template $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetCheckoutUrl($subject, $result)
    {
        if ($this->config->getEnabled()) {
            return $subject->getUrl('billmate/checkout');
        }
        return $result;
    }
}