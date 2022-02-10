<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\Price;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\PriceFactory;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;

/**
 * Same data structure as Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ResponseArticle,
 * This should be used when building an API request
 * Use ResponseArticle for handling API responses
 */
class Article extends DataObject
{
    /**
     * @var PriceFactory
     */
    private $priceFactory;

    /**
     * Mapped to SKU
     *
     * @var string
     */
    private $artnr;
    
    /**
     * Mapped to (product) name
     *
     * @var string
     */
    private $title;

    /**
     * Mapped to qty
     *
     * @var int|float
     */
    private $quantity;

    /**
     * @var int
     */
    private $taxrate;

    /**
     * Contains aprice, withouttax, and discount
     *
     * @var Price
     */
    private $priceModel;

    public function __construct(
        PriceFactory $priceFactory,
        array $data = []
    ) {
        $this->priceFactory = $priceFactory;
        parent::__construct($data);
    }

    /**
     * Initialize from quote item. Used for initCheckout and updateCheckout operations.
     *
     * @param Item $quoteItem
     * @return void
     */
    public function initializeByQuoteItem($quoteItem)
    {
        $this->artnr = $quoteItem->getSku();
        $this->title = $quoteItem->getName();
        $this->quantity = $quoteItem->getQty();
        $this->taxrate = (int)$quoteItem->getTaxPercent();
        $this->handlePrice($quoteItem);
    }

    /**
     * Initialize as discount
     *
     * @param string $name Name to use
     * @param int|float $amount Discount amount as positive value
     * @param int $taxRate Tax rate as percent
     * @return void
     */
    public function initializeAsDiscount($name, $amount, $taxRate)
    {
        $this->artnr = 'Discount_VAT' . $taxRate;
        $this->title = $name;
        $this->quantity = 1;
        $this->taxrate = $taxRate;
        $this->handleDiscountAmount($amount);
    }

    /**
     * Initialize from order item. Used for credit operation.
     *
     * @param OrderItem $crMemoItem
     * @return void
     */
    public function initializeForCredit(CreditMemoItem $crMemoItem)
    {
        $this->artnr = $crMemoItem->getSku();
        $this->title = $crMemoItem->getName();
        $this->quantity = $crMemoItem->getQty();
        $this->taxrate = (int)$crMemoItem->getOrderItem()->getTaxPercent();
        $this->handlePriceForCredit($crMemoItem);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function propertiesToArray(): array
    {
        $result = [
            'artnr' => $this->artnr,
            'title' => $this->title,
            'quantity' => $this->quantity,
            'taxrate' => $this->taxrate
        ];

        return array_merge($result, $this->priceModel->propertiesToArray());
    }

    /**
     * Initializes price model
     *
     * @param Item $quoteItem
     * @return void
     */
    private function handlePrice($quoteItem)
    {
        $this->priceModel = $this->priceFactory->create();
        $this->priceModel->initializeByQuoteItem($quoteItem);
    }

    /**
     * Initialize price model for credit operation
     *
     * @param CreditMemoItem $crMemoItem
     * @return void
     */
    private function handlePriceForCredit($crMemoItem)
    {
        $this->priceModel = $this->priceFactory->create();
        $this->priceModel->initializeForCredit($crMemoItem);
    }

    /**
     * Initializes price model as discount
     *
     * @param int|float $amount Discount amount as positive value
     * @return void
     */
    private function handleDiscountAmount($amount)
    {
        $this->priceModel = $this->priceFactory->create();
        $this->priceModel->initializeAsDiscount($amount);
    }
}
