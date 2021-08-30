<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;

abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @param BillmateAdapter $adapter
     */
    public function __construct(BillmateAdapter $adapter)
    {
        $this->adapter = $adapter;
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
