<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Validator;

use Magento\Framework\DataObject;
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
        // Error messages are set by the respectice Transaction classes.
        $errorObj = $validationSubject['response'][self::KEY_ERROR] ?? null;
        if ($errorObj instanceof DataObject) {
            // Build an error message to be displayed
            $msgFormat = 'Operation failed. %s';
            $errorDetail = sprintf('Message: %s', $errorObj->getMessage());
            $errorCode = $errorObj->getCode();

            if ($errorCode) {
                $errorDetail = sprintf('Code: %s. Message: %s', $errorCode, $errorObj->getMessage());
            }

            return $this->createResult(
                false,
                [sprintf($msgFormat, $errorDetail)]
            );
        }

        // If no errors were set, the response is considered valid.
        return $this->createResult(true);
    }
}
