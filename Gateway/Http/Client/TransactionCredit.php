<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Helper\CentsFormatter;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Payment\Gateway\Http\ClientException;

class TransactionCredit extends AbstractTransaction
{   
    use CentsFormatter;

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
        DiscountsHandler $discountsHandler,
        BillmateAdapter $billmateAdapter,
        DataUtil $dataUtil
    ) {
        $this->articleFactory = $articleFactory;
        $this->discountsHandler = $discountsHandler;
        parent::__construct($billmateAdapter, $dataUtil);
    }

    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[ResponseValidator::KEY_INVOICE_NUMBER];
        $credentials = $data['credentials'];

        $payment = $this->getPayment($data);
        $creditMemo = $payment->getCreditmemo();
        $partCredit = ($creditMemo->getGrandTotal() < $payment->getAmountPaid());

        $creditRequestData = array_filter([
            'PaymentData' => [
                'number' => $invoiceNumber,
                'partcredit' => $partCredit
            ],
            'Articles' => ($partCredit) ? $this->generateCreditArticles($creditMemo) : [],
            'Cart' => ($partCredit) ? $this->generateCreditCart($creditMemo) : []
        ]);
        $requestObject = $this->dataUtil->createDataObject($creditRequestData);

        $result = [];
        try {
            $response = $this->adapter->creditPayment($requestObject, $credentials);
            $result[ResponseValidator::KEY_STATUS] = $response->getData('status');
            $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
        } catch (\Exception $e) {
            $result[ResponseValidator::KEY_ERROR] = $e->getMessage();
            throw $e; // TODO remove and implement proper validation
        }

        return $result;
    }

    /**
     * @param array $data
     * @return Payment
     */
    private function getPayment(array $data): Payment
    {
        $payment = $data['payment'] ?? null;
        if (!$payment instanceof Payment) {
            throw new ClientException(__('Unable to process refund at this time'));
        }

        return $payment;
    }

    /**
     * Generate Articles for credit operation
     *
     * @param Creditmemo $crMemo
     * @return array
     */
    private function generateCreditArticles(Creditmemo $crMemo): array
    {
        $articlesToCredit = [];
        foreach ($crMemo->getItems() as $crMemoItem) {
            if (!$crMemoItem->getQty()) {
                continue;
            }

            $article = $this->articleFactory->create();
            $article->initializForCredit($crMemoItem);
            $articlesToCredit[] = $article->propertiesToArray();
        }

        $discountArticles = $this->discountsHandler->toArticles();

        foreach ($discountArticles as $discountArticle) {
            $articlesToCredit[] = $discountArticle->propertiesToArray();
        }

        return $articlesToCredit;
    }

    /**
     * Generate the Cart section for API calls
     *
     * @param Creditmemo $crMemo
     * @return array
     */
    private function generateCreditCart(Creditmemo $crMemo): array
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
