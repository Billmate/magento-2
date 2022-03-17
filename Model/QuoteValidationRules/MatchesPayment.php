<?php

namespace Billmate\NwtBillmateCheckout\Model\QuoteValidationRules;

use Billmate\NwtBillmateCheckout\Model\Service\ReturnRequestData;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\PaymentDataBuilder;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\ArticleGenerator;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\CartGenerator;
use Magento\Quote\Model\Quote;
use Magento\Framework\HTTP\AsyncClient\HttpException;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Payment\Gateway\Http\ClientException;

/**
 * Validate that quote matches Billmate payment
 */
class MatchesPayment implements QuoteValidationRuleInterface
{
    /**
     * Set this value to 1 on quote payment additional information when this validation should apply
     */
    const KEY_VALIDATE_PAYMENT_MATCH = 'validate_payment_match';

    /**
     * @var ValidationResultFactory
     */
    private $resultFactory;

    /**
     * @var ReturnRequestData
     */
    private $returnRequestData;

    /**
     * @var ArticleGenerator
     */
    private $articleGenerator;

    /**
     * @var CartGenerator
     */
    private $cartGenerator;

    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    /**
     * Storage for processed Quote
     *
     * @var Quote|null
     */
    private $processedQuote = null;

    public function __construct(
        ValidationResultFactory $resultFactory,
        ReturnRequestData $returnRequestData,
        ArticleGenerator $articleGenerator,
        CartGenerator $cartGenerator,
        BillmateAdapter $billmateAdapter
    ) {
        $this->resultFactory = $resultFactory;
        $this->returnRequestData = $returnRequestData;
        $this->articleGenerator = $articleGenerator;
        $this->cartGenerator = $cartGenerator;
        $this->billmateAdapter = $billmateAdapter;
    }

    /**
     * @inheritDoc
     */
    public function validate(Quote $quote): array
    {
        // Validate only if required
        $validateMatch = $quote->getPayment()->getAdditionalInformation(self::KEY_VALIDATE_PAYMENT_MATCH);
        if (!$validateMatch) {
            return [$this->resultFactory->create(['errors' => []])];
        }

        $this->processedQuote = $quote;
        $valid = false;
        $requestContent = $this->returnRequestData->getRequestContent();

        if (null === $requestContent) {
            return $this->returnError();
        }

        $valid = false;
        try {
            $valid = $this->compareWithPayment();
        } catch (\Exception $e) {
            return $this->returnError();
        }

        if (!$valid) {
            return $this->returnError();
        }

        return [$this->resultFactory->create(['errors' => []])];
    }

    /**
     * Compare quote contents with matching payment from Billmate
     * All the quote generated values are casted to string, to be the same type as received from the API call
     *
     * @return boolean
     * @throws ClientException
     * @throws HttpException
     */
    private function compareWithPayment(): bool
    {
        $quote = $this->processedQuote;
        $requestContent = $this->returnRequestData->getRequestContent();
        $pInfo = $this->billmateAdapter->getPaymentInfo($requestContent->getDataByPath('data/number'));

        $quoteCart = $this->cartGenerator->generateCart($quote);
        $quoteArticles = $this->articleGenerator->generateArticles($quote);

        // Compare totals
        $quoteWithoutTax = (string)$quoteCart['Total']['withouttax'] ?? null;
        if ($pInfo->getCart()->getTotal()->getWithouttax() !== $quoteWithoutTax) {
            return false;
        }

        $quoteTax = (string)$quoteCart['Total']['tax'] ?? null;
        if ($pInfo->getCart()->getTotal()->getTax() !== $quoteTax) {
            return false;
        }

        if (isset($quoteCart['Shipping'])) {
            $quoteShipping = (string)$quoteCart['Shipping']['withouttax'] ?? null;
            if ($pInfo->getCart()->getShipping()->getWithouttax() !== $quoteShipping) {
                return false;
            }
        }

        // Sort and compare arrays of articles
        $quoteItemArray = [];
        foreach ($quoteArticles as $quoteArticle) {
            $sku = (string)$quoteArticle['artnr'] ?? null;
            $qty = (string)$quoteArticle['quantity'] ?? null;
            $rowTotal = (string)$quoteArticle['withouttax'] ?? null;
            $quoteItemArray[$sku] = [
                'qty' => $qty,
                'rowTotal' => $rowTotal
            ];
        }

        $articleItemArray = [];
        foreach ($pInfo->getArticles() as $article) {
            $sku = $article->getArtnr();
            $qty = $article->getQuantity();
            $rowTotal = $article->getWithouttax();
            $articleItemArray[$sku] = [
                'qty' => $qty,
                'rowTotal' => $rowTotal
            ];
        }

        if ($quoteItemArray !== $articleItemArray) {
            return false;
        }

        return true;
    }

    /**
     * Cancel payment and return invalid status with error message
     *
     * @return array
     * @throws ClientException
     * @throws HttpException
     */
    private function returnError()
    {
        $requestContent = $this->returnRequestData->getRequestContent();

        if (null !== $requestContent) {
            $this->processedQuote->getPayment()->unsAdditionalInformation(PaymentDataBuilder::PAYMENT_NUMBER);
            $this->billmateAdapter->cancelPayment($requestContent->getDataByPath('data/number'));
        }

        $error = __(
            'Your cart differs from the payment. '
            . 'Please try placing the order again. '
            . 'Avoid updating your cart between starting and finishing the payment.'
        );

        return [$this->resultFactory->create(['errors' => [$error]])];
    }
}
