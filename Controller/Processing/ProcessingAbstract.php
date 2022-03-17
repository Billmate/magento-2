<?php

namespace Billmate\NwtBillmateCheckout\Controller\Processing;

use Billmate\NwtBillmateCheckout\Model\Service\ReturnRequestData;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Abstract class for processing controllers
 */
abstract class ProcessingAbstract implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ControllerUtil
     */
    protected $util;

    /**
     * @var DataUtil
     */
    protected $dataUtil;

    /**
     * @var OrderUtil
     */
    protected $orderUtil;

    /**
     * @var ReturnRequestData
     */
    protected $returnRequestData;

    public function __construct(
        ControllerUtil $util,
        DataUtil $dataUtil,
        OrderUtil $orderUtil,
        ReturnRequestData $returnRequestData
    ) {
        $this->util = $util;
        $this->dataUtil = $dataUtil;
        $this->orderUtil = $orderUtil;
        $this->returnRequestData = $returnRequestData;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    abstract public function validateForCsrf(RequestInterface $request): ?bool;

    /**
     * Set request content property in service class
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function extractContent(): void
    {
        $credentials = $this->dataUtil->unserialize($this->util->getRequest()->getParam('credentials', ''));
        $data = $this->dataUtil->unserialize($this->util->getRequest()->getParam('data', ''));
        $requestContent = $this->dataUtil->createDataObject(['credentials' => $credentials, 'data' => $data]);
        $this->returnRequestData->setRequestContent($requestContent);
    }
}
