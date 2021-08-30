<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\PaymentDataBuilder;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;

class TransactionAuthorize extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function process(array $data)
    {
        $invoiceNumber = $data[PaymentDataBuilder::INVOICE_NUMBER];
        try {
            $paymentInfo = $this->adapter->getPaymentInfo($invoiceNumber);
        } catch (\Exception $e) {
            throw $e; // TODO handle appropriately
        }

        $methodId = $paymentInfo->getPaymentData()->getMethod();
        $methodDescription = Config::PAYMENT_METHOD_MAPPING[$methodId];

        return [
            PaymentDataBuilder::INVOICE_NUMBER => $invoiceNumber,
            'billmate_method_id' => $methodId,
            'billmate_method_description' => $methodDescription
        ];
    }
}
