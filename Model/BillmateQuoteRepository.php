<?php

namespace Billmate\NwtBillmateCheckout\Model;

use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

/**
 * Alternate quote repository class for placing order with Billmate
 */
class BillmateQuoteRepository extends QuoteRepository
{
    /**
     * We use @see \Magento\Quote\Model\QuoteManagement::placeOrder when completing an order with Billmate Checkout.
     * But we need it to load an inactive quote, and also always use the email from the iframe as quote email.
     * So we overwrite QuoteRepository::getActive in that context only.
     *
     * @param int $cartId
     * @param array $sharedStoreIds
     * @return CartInterface
     */
    public function getActive($cartId, array $sharedStoreIds = [])
    {
        $quote = $this->get($cartId, $sharedStoreIds);
        /** @var Quote $quote */
        $quote->setCustomerEmail($quote->getPayment()->getAdditionalInformation('billmate_order_email'));
        return $quote;
    }
}