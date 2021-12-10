<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Helper\SubjectReader;

class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDO = $this->subjectReader->readPayment($subject);
        $canVoidFlag = true;
        $payment = $paymentDO->getPayment();
        if ((bool)$payment->getAmountPaid()) {
            $canVoidFlag = false;
        }
        if ($payment->getAmountPaid() < $payment->getAmountAuthorized() && (bool)$payment->getAmountPaid()) {
            $canVoidFlag = true;
        }
        return $payment instanceof Payment && $canVoidFlag;
    }
}
