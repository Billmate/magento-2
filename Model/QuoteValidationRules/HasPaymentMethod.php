<?php

namespace Billmate\NwtBillmateCheckout\Model\QuoteValidationRules;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Magento\Framework\Validation\ValidationResultFactory;

class HasPaymentMethod implements QuoteValidationRuleInterface
{
    private $resultFactory;

    public function __construct(
        ValidationResultFactory $resultFactory
    ) {
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function validate(Quote $quote): array
    {
        $methodId = $quote->getPayment()->getAdditionalInformation(ResponseValidator::KEY_METHOD_ID);
        $methodDescription = $quote->getPayment()->getAdditionalInformation(ResponseValidator::KEY_METHOD_DESCRIPTION);
        $validationErrors = [];

        if (!$methodId || !$methodDescription || $methodDescription !== Config::PAYMENT_METHOD_MAPPING[$methodId]) {
            $validationErrors[] = __(
                'Payment method not registered, '
                . 'please choose your preferred payment method again and click Purchase again.'
            );
        }

        return [$this->resultFactory->create(['errors' => $validationErrors])];
    }
}
