<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Response;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class PaymentDetailsHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->getPayment($handlingSubject);
        $payment->setAdditionalInformation(
            ResponseValidator::KEY_METHOD_ID,
            $response[ResponseValidator::KEY_METHOD_ID]
        );
        $payment->setAdditionalInformation(
            ResponseValidator::KEY_METHOD_DESCRIPTION,
            $response[ResponseValidator::KEY_METHOD_DESCRIPTION]
        );
    }
}
