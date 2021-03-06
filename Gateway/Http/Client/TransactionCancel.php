<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class TransactionCancel extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[ResponseValidator::KEY_INVOICE_NUMBER];
        $credentials = $data['credentials'];

        $result = [];
        $response = $this->adapter->cancelPayment($invoiceNumber, $credentials);
        $result[ResponseValidator::KEY_STATUS] = $response->getData('status');
        $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;

        return $result;
    }
}
