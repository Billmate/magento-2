<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;

abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var BillmateAdapter
     */
    protected $adapter;

    /**
     * @var DataUtil
     */
    protected $dataUtil;

    /**
     * @param BillmateAdapter $adapter
     */
    public function __construct(
        BillmateAdapter $adapter,
        DataUtil $dataUtil
    ) {
        $this->adapter = $adapter;
        $this->dataUtil = $dataUtil;
    }

    /**
     * @inheritDoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        return $this->process($data);
    }

    /**
     * Process transfer request
     *
     * @param array $data
     * @return array
     */
    abstract protected function process(array $data);
}
