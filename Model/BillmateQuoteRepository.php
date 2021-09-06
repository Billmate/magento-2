<?php

namespace Billmate\NwtBillmateCheckout\Model;

use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Alternate quote repository class for placing order with Billmate
 */
class BillmateQuoteRepository extends QuoteRepository
{
    /**
     * We use @see \Magento\Quote\Model\QuoteManagement::placeOrder when completing an order with Billmate Checkout.
     * But we need it to load an inactive quote. So we overwrite QuoteRepository::getActive in that context only.
     *
     * @param int $cartId
     * @param array $sharedStoreIds
     * @return CartInterface
     */
    public function getActive($cartId, array $sharedStoreIds = [])
    {
        return $this->get($cartId, $sharedStoreIds);
    }
}