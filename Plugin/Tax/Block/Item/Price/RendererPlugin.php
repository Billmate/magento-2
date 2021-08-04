<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Tax\Block\Item\Price;

use Magento\Tax\Block\Item\Price\Renderer;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;

class RendererPlugin
{
    /**
     * Add KO data binding to price element
     *
     * @param Renderer $subject
     * @param string $result
     * @return string
     */
    public function afterFormatPrice($subject, $result)
    {

        $item = $subject->getItem();
        $dataBindType = $subject->getDataBindType();
        if (!$item instanceof QuoteItem || !$dataBindType) {
            return $result;
        }

        $dataBoundSpan = sprintf('<span data-bind="text: %s" ', $dataBindType);
        return str_replace('<span ', $dataBoundSpan, $result);
    }
}
