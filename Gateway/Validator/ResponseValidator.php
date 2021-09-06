<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;

class ResponseValidator extends AbstractValidator
{
    const KEY_INVOICE_NUMBER = 'billmate_invoice_number';
    const KEY_STATUS = 'billmate_status';
    const KEY_METHOD_ID = 'billmate_method_id';
    const KEY_METHOD_DESCRIPTION = 'billmate_method_description';
    const KEY_ERROR = 'error';

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject)
    {
        if (isset($validationSubject[self::KEY_ERROR])) {
            return $this->createResult(
                false,
                [$validationSubject[self::KEY_ERROR]]
            );
        }
        return $this->createResult(true); //TODO do actual validation
    }
}
