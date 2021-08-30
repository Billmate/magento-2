<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Helper\SubjectReader;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Gets appropriate payment object type
     *
     * @param array $handlingSubject
     * @return Payment
     */
    protected function getPayment(array $handlingSubject): Payment
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        return $paymentDO->getPayment();
    }

    abstract public function handle(array $handlingSubject, array $response): void;
}
