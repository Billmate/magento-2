<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

use Magento\Payment\Helper\Formatter;

class PaymentDataBuilder extends AbstractDataBuilder
{
    use Formatter;

    /**
     * The billing amount of the request. This value must be greater than 0,
     * and must match the currency format of the merchant account.
     */
    const AMOUNT = 'amount';

    /**
     * Payment ID
     */
    const PAYMENT_ID = 'billmatePaymentId';

    /**
     * The merchant account ID used to create a transaction.
     * If no merchant account ID is specified, default account is used.
     */
    const MERCHANT_ID = 'merchantID';

    /**
     * Order ID Key
     */
    const ORDER_ID = 'orderId';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $result = [
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($buildSubject)),
            self::PAYMENT_ID => $payment->getAdditionalInformation(
                self::PAYMENT_ID
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