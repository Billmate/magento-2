<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart;

use Billmate\NwtBillmateCheckout\Gateway\Helper\CentsFormatter;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Framework\DataObject;

class CartGenerator
{
    use CentsFormatter;

    /**
     * Generate the Cart section for API calls
     *
     * @param Quote|Creditmemo $subject Quote ir Credit Memo to use
     * @return array
     */
    public function generateCart(DataObject $subject)
    {
        if ($subject instanceof Quote) {
            return $this->generateFromQuote($subject);
        }

        if ($subject instanceof Creditmemo) {
            return $this->generateFromCrMemo($subject);
        }

        return [];
    }

     /**
      * Generate cart based on a Quote
      *
      * @param Quote $quote
      * @return array
      */
    private function generateFromQuote(Quote $quote): array
    {
        $cart = [];

        $calculationAddress = ($quote->isVirtual()) ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $discountTaxComp = $calculationAddress->getDiscountTaxCompensationAmount();
        $discountAmount = $calculationAddress->getDiscountAmount();
        $withoutTax = $quote->getSubtotal() + $discountAmount + $discountTaxComp;
        $taxAmount = $calculationAddress->getTaxAmount();
        $total = [
            'withouttax' => $this->toCents($withoutTax),
            'tax' => $this->toCents($taxAmount),
            'withtax' => $this->toCents($quote->getGrandTotal())
        ];

        if (!$quote->isVirtual()) {
            $shippingInclTax = $this->toCents($quote->getShippingAddress()->getShippingInclTax());

            if ($shippingInclTax > 0) {
                $shippingTaxAmount = $this->toCents($quote->getShippingAddress()->getShippingTaxAmount());
                $shippingExclTax = (int)$shippingInclTax - $shippingTaxAmount;
                $shipping = [
                    'withouttax' => $shippingExclTax,
                    'taxrate' => $this->toCents($shippingTaxAmount / $shippingExclTax),
                    'withtax' => $shippingInclTax,
                    'method' => $quote->getShippingAddress()->getShippingDescription(),
                    'method_code' => $quote->getShippingAddress()->getShippingMethod()
                ];
    
                $cart['Shipping'] = $shipping;
                $total['withouttax'] += $shippingExclTax;
            }
        }

        $cart['Total'] = $total;
        return $cart;
    }

    /**
     * Generate cart based on a Credit Memo
     *
     * @param Creditmemo $crMemo
     * @return void
     */
    private function generateFromCrMemo(Creditmemo $crMemo)
    {
        $cart = [];

        $discountTaxComp = $crMemo->getDiscountTaxCompensationAmount();
        $discountAmount = $crMemo->getDiscountAmount();
        $withoutTax = $crMemo->getSubtotal() + $discountAmount + $discountTaxComp;
        $taxAmount = $crMemo->getTaxAmount();
        $total = [
            'withouttax' => $this->toCents($withoutTax),
            'tax' => $this->toCents($taxAmount),
            'withtax' => $this->toCents($crMemo->getGrandTotal())
        ];

        $shippingExclTax = $this->toCents($crMemo->getShippingAmount());

        if ($shippingExclTax > 0) {
            $shippingTaxAmount = $this->toCents($crMemo->getShippingTaxAmount());
            $shippingInclTax = (int)$shippingExclTax + $shippingTaxAmount;
            $shipping = [
                'withouttax' => $shippingExclTax,
                'taxrate' => $this->toCents($shippingTaxAmount / $shippingExclTax),
                'withtax' => $shippingInclTax
            ];

            $cart['Shipping'] = $shipping;
            $total['withouttax'] += $shippingExclTax;
        }

        $cart['Total'] = $total;
        return $cart;
    }
}
