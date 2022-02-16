<?php

namespace Billmate\NwtBillmateCheckout\Controller\Processing;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Model\Service\ReturnRequestData;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * For when customer cancels payment on third party service
 */
class Cancel extends ProcessingAbstract
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        ControllerUtil $util,
        DataUtil $dataUtil,
        OrderUtil $orderUtil,
        ReturnRequestData $returnRequestData,
        ManagerInterface $messageManager
    ) {
        parent::__construct($util, $dataUtil, $orderUtil, $returnRequestData);
        $this->messageManager = $messageManager;
    }

    /**
     * Restore quote
     *
     * @return AbstractResult
     */
    public function execute()
    {
        $orderId = $this->returnRequestData->getRequestContent()->getDataByPath('data/orderid');
        $this->messageManager->addNoticeMessage('You canceled the payment, but you can still proceed with your order.');
        return $this->util->redirect('checkout/cart', ['reserved' => $orderId]);
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
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($this->util->getRequest()->getMethod() !== 'POST') {
            return false;
        }

        try {
            $this->extractContent();
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (!$this->dataUtil->verifyHash($this->returnRequestData->getRequestContent())) {
            return false;
        }

        return true;
    }
}
