<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

use Magento\Quote\Model\Quote\Item;

class UpdatePostPlugin extends AbstractPlugin
{
    protected function constructSuccessResponse(): array
    {
        $items = $this->getQuoteItems();
        $responseData = ['items' => []];
        foreach ($items as $item) {
            $responseData['items'][$item->getItemId()] = $item->toArray();
        }
        return $responseData;
    }

    /**
     * @return Item[]
     */
    private function getQuoteItems()
    {
        return $this->checkoutSession->getQuote()->getItems();
    }
}
