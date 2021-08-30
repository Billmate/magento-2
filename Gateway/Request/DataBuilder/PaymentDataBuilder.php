<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

use Magento\Payment\Helper\Formatter;

class PaymentDataBuilder extends AbstractDataBuilder
{
    use Formatter;

    /**
     * Billmate Invoice ID
     */
    const INVOICE_NUMBER = 'billmate_invoice_number';

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
            self::INVOICE_NUMBER=> $payment->getAdditionalInformation(
                self::INVOICE_NUMBER
            ),
            self::ORDER_ID => $order->getOrderIncrementId()
        ];

        $merchantAccountId = $this->config->getMerchantAccountId();
        if (!empty($merchantAccountId)) {
            $result[self::MERCHANT_ID] = $merchantAccountId;
        }

        return $result;
    }
}
