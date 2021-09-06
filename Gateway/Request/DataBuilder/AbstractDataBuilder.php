<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\DataObjectFactory;

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

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Reads payment from subject
     * Wrapper for Magento\Payment\Gateway\Helper\SubjectReader::readPayment
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    protected function readPayment(array $subject)
    {
        return $this->subjectReader->readPayment($subject);
    }
}
