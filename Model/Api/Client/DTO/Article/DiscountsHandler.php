<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

/**
 * Can collect discounts during Article generation, then convert them to the proper format for API calls to Billmate
 */
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
     * @param int|float $taxCompAmount
     * @return void
     */
    public function add($amount, $taxRate, $taxCompAmount)
    {
        $taxRateKey = $taxRate;
        if (!isset($this->discountsByTaxRates[$taxRateKey])) {
            $this->discountsByTaxRates[$taxRateKey] = [
                'amount' => $amount,
                'taxCompensationAmount' => $taxCompAmount
            ];
            return;
        }

        $this->discountsByTaxRates[$taxRateKey]['amount'] += $amount;
        $this->discountsByTaxRates[$taxRateKey]['taxCompensationAmount'] += $taxCompAmount;
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
            $article->initializeAsDiscount(sprintf('Discount %s%% Tax', $taxRate), $discountAmount, $taxRate);
            $articles[] = $article;
        }

        return $articles;
    }
}
