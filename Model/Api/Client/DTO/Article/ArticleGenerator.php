<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Creditmemo;

class ArticleGenerator
{
    /**
     * @var ArticleFactory
     */
    private $articleFactory;

    /**
     * @var DiscountsHandler
     */
    private $discountsHandler;

    public function __construct(
        ArticleFactory $articleFactory,
        DiscountsHandler $discountsHandler
    ) {
        $this->articleFactory = $articleFactory;
        $this->discountsHandler = $discountsHandler;
    }

    /**
     * Generate the Articles section for API calls
     *
     * @param Quote|Creditmemo $subject Quote or Credit Memo to use
     * @return array
     */
    public function generateArticles($subject): array
    {
        if ($subject instanceof Quote) {
            return $this->generateFromQuote($subject);
        }

        if ($subject instanceof Creditmemo) {
            return $this->generateFromCrMemo($subject);
        }

        return [];
    }

    private function generateFromQuote(Quote $quote): array
    {
        $articles = [];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            /** @var Item $quoteItem */
            if ($quoteItem->getProductType() === BundleType::TYPE_CODE) {
                foreach ($quoteItem->getChildren() as $bundleChildItem) {
                    $article = $this->articleFactory->create();
                    $article->initializeByQuoteItem($bundleChildItem);
                    $articles[] = $article->propertiesToArray();
                }
            }
            $article = $this->articleFactory->create();
            $article->initializeByQuoteItem($quoteItem);
            $articles[] = $article->propertiesToArray();
        }

        $discountArticles = $this->discountsHandler->toArticles();

        foreach ($discountArticles as $discountArticle) {
            $articles[] = $discountArticle->propertiesToArray();
        }

        return $articles;
    }

    private function generateFromCrMemo(Creditmemo $crMemo): array
    {
        $articlesToCredit = [];
        foreach ($crMemo->getItems() as $crMemoItem) {
            if (!$crMemoItem->getQty()) {
                continue;
            }

            $article = $this->articleFactory->create();
            $article->initializeForCredit($crMemoItem);
            $articlesToCredit[] = $article->propertiesToArray();
        }

        $discountArticles = $this->discountsHandler->toArticles();

        foreach ($discountArticles as $discountArticle) {
            $articlesToCredit[] = $discountArticle->propertiesToArray();
        }

        return $articlesToCredit;
    }
}
