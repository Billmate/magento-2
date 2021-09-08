<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\PaymentDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class TransactionAuthorize extends AbstractTransaction
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
            $paymentInfo = $this->adapter->getPaymentInfo($invoiceNumber, $credentials);
            $methodId = $paymentInfo->getPaymentData()->getMethod();
            $methodDescription = Config::PAYMENT_METHOD_MAPPING[$methodId];
            $status = $paymentInfo->getPaymentData()->getStatus();
            $result[ResponseValidator::KEY_INVOICE_NUMBER] = $invoiceNumber;
            $result[ResponseValidator::KEY_METHOD_ID] = $methodId;
            $result[ResponseValidator::KEY_METHOD_DESCRIPTION] = $methodDescription;
            $result[ResponseValidator::KEY_STATUS] = $status;
        } catch (\Exception $e) {
            $result[ResponseValidator::KEY_ERROR] = $e->getMessage();
        }

        return $result;
    }
}
