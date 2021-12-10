<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Response;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class TransactionIdHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $txnId = $response[ResponseValidator::KEY_INVOICE_NUMBER] . '-' . $response[ResponseValidator::KEY_STATUS];
        $payment = $this->getPayment($handlingSubject);
        $payment->setTransactionId($txnId)->setIsTransactionClosed(false);
    }
}
