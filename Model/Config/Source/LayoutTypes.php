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
        return [['value' => '1column', 'label' => __('1 column')], ['value' => '2columns', 'label' => __('2 columns')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['1column' => __('1 column'), '2columns' => __('2 columns')];
    }
}
