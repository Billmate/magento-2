<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\Price;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\PriceFactory;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;

/**
 * Same data structure as Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ResponseArticle,
 * This should be used when handling building an API request
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
     * Initialize from quote item
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
        $this->discount = 0;
        $this->taxrate = $taxRate;
        $this->handleDiscountAmount($amount);
    }

    /**
     * Convert to array
     *
     * @return void
     */
    public function propertiesToArray()
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
