<?php

namespace Billmate\NwtBillmateCheckout\Model\Config\Source;

class LayoutTypes implements \Magento\Framework\Data\OptionSourceInterface 
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '1column-billmate', 'label' => __('1 column Billmate')], ['value' => '2columns-billmate', 'label' => __('2 columns Billmate')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['1column-billmate' => __('1 column Billmate'), '2columns-billmate' => __('2 columns Billmate')];
    }
}
