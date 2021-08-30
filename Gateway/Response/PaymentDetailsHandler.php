<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

class PaymentDetailsHandler extends AbstractHandler
{
    const METHOD_ID = 'billmate_method_id';
    const METHOD_DESCRIPTION = 'billmate_method_description';

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->getPayment($handlingSubject);
        $payment->setAdditionalInformation('billmate_method_id', $response[self::METHOD_ID]);
        $payment->setAdditionalInformation('billmate_method_description', $response[self::METHOD_DESCRIPTION]);
    }
}
