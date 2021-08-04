<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

class CustomerDataBuilder extends AbstractDataBuilder
{
    /**
     * Customer block name
     */
    const CUSTOMER = 'customer';

    /**
     * The first name value must be less than or equal to 255 characters.
     */
    const FIRST_NAME = 'firstName';

    /**
     * The last name value must be less than or equal to 255 characters.
     */
    const LAST_NAME = 'lastName';

    /**
     * The customer’s company. 255 character maximum.
     */
    const COMPANY = 'company';

    /**
     * The customer’s email address, comprised of ASCII characters.
     */
    const EMAIL = 'email';

    /**
     * Phone number. Phone must be 10-14 characters and can
     * only contain numbers, dashes, parentheses and periods.
     */
    const PHONE = 'phone';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();

        return [
            self::CUSTOMER => [
                self::FIRST_NAME => $billingAddress->getFirstname(),
                self::LAST_NAME => $billingAddress->getLastname(),
                self::COMPANY => $billingAddress->getCompany(),
                self::PHONE => $billingAddress->getTelephone(),
                self::EMAIL => $billingAddress->getEmail(),
            ]
        ];
    }
}
