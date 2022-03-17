<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Validator;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;

class ErrorMessageMapper implements ErrorMessageMapperInterface
{
    /**
     * @inheritDoc
     */
    public function getMessage(string $code)
    {
        /**
         * This only needs to return the parameter $code as a Phrase
         * The parameter already contains the full error message at this point
         * (See Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator::validate)
         */
        return __($code);
    }
}
