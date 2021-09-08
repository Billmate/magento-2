<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class TransactionActivate extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[ResponseValidator::KEY_INVOICE_NUMBER];
        $credentials = $data['credentials'];
        $result = [];
        try {
            $response = $this->adapter->activatePayment($invoiceNumber, $credentials);
            $result[ResponseValidator::KEY_STATUS] = $response->getData('status');
            $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
        } catch (\Exception $e) {
            $result[ResponseValidator::KEY_ERROR] = $e->getMessage();
        }

        return $result;
    }
}
