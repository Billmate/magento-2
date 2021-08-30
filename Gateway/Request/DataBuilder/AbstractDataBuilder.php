<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

abstract class AbstractDataBuilder implements BuilderInterface
{
    /**
     * Config provider
     *
     * @var Config
     */
    protected $config;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * Undocumented function
     *
     * @param array $payment
     * @return PaymentDataObjectInterface
     */
    protected function readPayment(array $payment)
    {
        return $this->subjectReader->readPayment($payment);
    }
}
