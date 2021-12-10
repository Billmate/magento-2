<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

use Magento\Payment\Helper\Formatter;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

class PaymentDataBuilder extends AbstractDataBuilder
{
    use Formatter;

    /**
     * Billmate Payment ID
     */
    const PAYMENT_NUMBER = 'billmate_payment_number';

    /**
     * The merchant account ID used to create a transaction.
     */
    const MERCHANT_ID = 'merchant_id';

    /**
     * Order ID Key
     */
    const ORDER_ID = 'order_id';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $result = [
            self::PAYMENT_NUMBER => $payment->getAdditionalInformation(
                self::PAYMENT_NUMBER
            ),
            ResponseValidator::KEY_INVOICE_NUMBER => $payment->getAdditionalInformation(
                ResponseValidator::KEY_INVOICE_NUMBER
            ),
            self::ORDER_ID => $order->getOrderIncrementId(),
            'payment' => $payment
        ];

        return $result;
    }
}
