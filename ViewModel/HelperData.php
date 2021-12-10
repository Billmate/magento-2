<?php

namespace Billmate\NwtBillmateCheckout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Tax\Helper\Data as TaxHelper;

class HelperData implements ArgumentInterface
{
    /**
     * @var TaxHelper
     */
    private $taxHelper;

    public function __construct(
        TaxHelper $taxHelper
    ) {
        $this->taxHelper = $taxHelper;
    }

    public function displayCartBothPrices($store = null)
    {
        return $this->taxHelper->displayCartBothPrices($store = null);
    }
}
