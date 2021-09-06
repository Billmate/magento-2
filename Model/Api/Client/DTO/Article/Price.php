<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;
use Magento\Bundle\Model\Product\Type as BundleType;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Magento\Framework\DataObject;
use Billmate\NwtBillmateCheckout\Gateway\Helper\CentsFormatter;

class Price extends DataObject
{
    use CentsFormatter;

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
    public function initializeByQuoteItem($quoteItem): void
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
            $this->discount = 0;
            return;
        }

        if ($quoteItem->getDiscountAmount() > 0) {
            $this->discountsHandler->add(
                $quoteItem->getDiscountAmount(),
                (int)$quoteItem->getTaxPercent(),
                $quoteItem->getDiscountTaxCompensationAmount()
            );
        }

        $this->aprice = $this->toCents($priceToConvert);
        $this->withouttax = $this->toCents($rowTotalToConvert);
        $this->discount = $quoteItem->getDiscountPercent() ?? 0;
    }

    /**
     * Initialize for use in the credit operation
     *
     * @param CreditMemoItem $crMemoItem
     * @return void
     */
    public function initializeForCredit(CreditMemoItem $crMemoItem): void
    {
        $this->aprice = $this->toCents($crMemoItem->getPrice());
        $this->withouttax = $this->toCents($crMemoItem->getRowTotal() + $crMemoItem->getWeeeTaxAppliedRowAmount());
        $this->discount = $crMemoItem->getOrderItem()->getDiscountPercent() ?? 0;

        if ($crMemoItem->getDiscountAmount() > 0) {
            $this->discountsHandler->add(
                $crMemoItem->getDiscountAmount(),
                (int)$crMemoItem->getOrderItem()->getTaxPercent(),
                $crMemoItem->getDiscountTaxCompensationAmount()
            );
        }
    }

    /**
     * Initialize for a Discount Article
     *
     * @param int|float $discountAmount Discount amount as positive value
     * @return void
     */
    public function initializeAsDiscount($discountAmount): void
    {
        $this->aprice = $this->toCents(-1 * $discountAmount);
        $this->withouttax = $this->toCents(-1 * $discountAmount);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function propertiesToArray(): array
    {
        return [
            'aprice' => $this->aprice,
            'withouttax' => $this->withouttax,
            'discount' => $this->discount
        ];
    }
}
