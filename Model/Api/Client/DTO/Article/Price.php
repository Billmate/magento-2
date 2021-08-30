<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

use Magento\Quote\Model\Quote\Item;
use Magento\Bundle\Model\Product\Type as BundleType;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Magento\Framework\DataObject;

class Price extends DataObject
{
    /**
     * @var DiscountsHandler
     */
    private $discountsHandler;

    /**
     * Calculated from product price excl tax
     *
     * @var int
     */
    private $aprice;

    /**
     * Calculated from row total excl tax
     *
     * @var int
     */
    private $withouttax;

    /**
     * Mapped to Discount Percent
     *
     * @var int
     */
    private $discount = 0;

    public function __construct(
        DiscountsHandler $discountsHandler
    ) {
        $this->discountsHandler = $discountsHandler;
    }

    /**
     * Initialize from Quote Item
     * Set price to 0 if item type is bundle and not using dynamic pricing
     *
     * @param Item $quoteItem
     * @return void
     */
    public function initializeByQuoteItem($quoteItem)
    {
        $priceToConvert = $quoteItem->getPrice();
        $rowTotalToConvert = $quoteItem->getRowTotal() + $quoteItem->getWeeeTaxAppliedRowAmount();
        $productType = $quoteItem->getProductType();
        $priceType = $quoteItem->getProduct()->getPriceType();

        // Pricetype 0 = bundle price is dynamic
        // In that case the bundle item should have prices set to 0. Its child items will have prices as normal
        if ($productType === BundleType::TYPE_CODE && (int)$priceType === 0) {
            $this->aprice = 0;
            $this->withouttax = 0;
            return;
        }

        if ($quoteItem->getDiscountAmount() > 0) {
            $this->discountsHandler->add(
                $quoteItem->getDiscountAmount(),
                $quoteItem->getTaxPercent(),
                $quoteItem->getDiscountTaxCompensationAmount()
            );
        }

        $this->aprice = $this->toCents($priceToConvert);
        $this->withouttax = $this->toCents($rowTotalToConvert);
    }

    /**
     * Initialize for a Discount Article
     *
     * @param int|float $discountAmount Discount amount as positive value
     * @return void
     */
    public function initializeAsDiscount($discountAmount)
    {
        $this->aprice = $this->toCents(-1 * $discountAmount);
        $this->withouttax = $this->toCents(-1 * $discountAmount);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function propertiesToArray()
    {
        return [
            'aprice' => $this->aprice,
            'withouttax' => $this->withouttax,
            'discount' => $this->discount
        ];
    }

    /**
     * Convert price to cents value
     *
     * @param int|float $value
     * @return int
     */
    private function toCents($value)
    {
        return (int)100 * $value;
    }
}
