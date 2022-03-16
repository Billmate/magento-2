<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Magento\Framework\Exception\PaymentException;

class TransactionActivate extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[ResponseValidator::KEY_INVOICE_NUMBER];
        if (!$invoiceNumber) {
            throw new PaymentException(
                __("Payment is missing a Billmate invoice number! Check for callback errors in Billmate's backend.")
            );
        }

        $credentials = $data['credentials'];
        $result = [];
        $response = $this->adapter->activatePayment($invoiceNumber, $credentials);
        $result[ResponseValidator::KEY_STATUS] = $response->getData('status');
        $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
        return $result;
    }
}
