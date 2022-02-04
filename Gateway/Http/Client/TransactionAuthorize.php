<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class TransactionAuthorize extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[ResponseValidator::KEY_INVOICE_NUMBER];
        $result = [];

        $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
        $result[ResponseValidator::KEY_STATUS] = 'authorized';

        return $result;
    }
}
