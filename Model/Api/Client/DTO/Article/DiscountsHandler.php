<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

class DiscountsHandler
{
    /**
     * @var ArticleFactory
     */
    private $articleFactory;

    /**
     * @var array
     */
    private $discountsByTaxRates = [];

    /**
     * @var int
     */
    private $discountsProcessed = 0;

    public function __construct(
        ArticleFactory $articleFactory
    ) {
        $this->articleFactory = $articleFactory;
    }

    /**
     * Add discount to collection
     *
     * @param int|float $amount Discount amount as a positive integer
     * @param int $taxRate Tax rate as a percentage (i.e. 25, not 0.25)
     * @param int|float $taxCompensationAmount
     * @return void
     */
    public function add($amount, $taxRate, $taxCompensationAmount)
    {
        $taxRateKey = $taxRate;
        $this->discountsProcessed++;
        if (!isset($this->discountsByTaxRates[$taxRateKey])) {
            $this->discountsByTaxRates[$taxRateKey] = [
                'amount' => $amount,
                'taxCompensationAmount' => $taxCompensationAmount
            ];
            return;
        }

        $this->discountsByTaxRates[$taxRateKey]['amount'] += $amount;
        $this->discountsByTaxRates[$taxRateKey]['taxCompensationAmount'] += $taxCompensationAmount;
    }

    /**
     * Convert collected discounts to array of Article objects
     *
     * @return Article[]
     */
    public function toArticles()
    {
        $articles = [];
        foreach ($this->discountsByTaxRates as $taxRate => $discountInfo) {
            $article = $this->articleFactory->create();
            $discountAmount = $discountInfo['amount'] - $discountInfo['taxCompensationAmount'];
            $article->initializeAsDiscount(sprintf('Discount %s%%', $taxRate), $discountAmount, $taxRate);
            $articles[] = $article;
        }

        return $articles;
    }
}
