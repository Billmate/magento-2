<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;

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
        try {
            return $this->process($data);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    protected function handleException(\Exception $exception): array
    {
        return [ResponseValidator::KEY_ERROR => $this->dataUtil->createDataObject([
            'code' => $exception->getCode(),
            'message' => $exception->getMessage()
        ])];
    }

    /**
     * Process transfer request
     *
     * @param array $data
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException;
     * @throws \Magento\Framework\HTTP\AsyncClient\HttpException;
     * @throws \Magento\Framework\Exception\PaymentException;
     */
    abstract protected function process(array $data);
}
