<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\ArticleGenerator;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\CartGenerator;
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
     * @var ArticleGenerator
     */
    private $articleGenerator;

    /**
     * @var CartFactory
     */
    private $cartGenerator;

    public function __construct(
        ArticleGenerator $articleGenerator,
        CartGenerator $cartGenerator,
        BillmateAdapter $billmateAdapter,
        DataUtil $dataUtil
    ) {
        $this->articleGenerator = $articleGenerator;
        $this->cartGenerator = $cartGenerator;
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
            'Articles' => ($partCredit) ? $this->articleGenerator->generateArticles($creditMemo) : [],
            'Cart' => ($partCredit) ? $this->cartGenerator->generateCart($creditMemo) : []
        ]);
        $requestObject = $this->dataUtil->createDataObject($creditRequestData);

        $result = [];
        $response = $this->adapter->creditPayment($requestObject, $credentials);
        $result[ResponseValidator::KEY_STATUS] = $response->getData('status');
        $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
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
}
