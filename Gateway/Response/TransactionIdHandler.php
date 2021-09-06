<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Response;

use Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder\PaymentDataBuilder;

class TransactionIdHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $txnId = $response[PaymentDataBuilder::INVOICE_NUMBER] . '-AUTH';
        $this->getPayment($handlingSubject)->setTransactionId($txnId);
    }
}
