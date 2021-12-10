<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Callback implements HttpGetActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var DataUtil
     */
    private $dataUtil;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
    }

    public function execute()
    {
        $result = $this->util->jsonResult();
        try {
            $content = $this->dataUtil->unserialize($this->request->getContent());
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Invalid data']);
        }

        if (!$this->dataUtil->verifyHash($content)) {
            return $result->setHttpResponseCode(403);
        }

        $contentObj = $this->dataUtil->createDataObject($content['data']);
        if (!is_numeric($contentObj->getOrderid())) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Invalid order id']);
        }

        $order = $this->orderUtil->loadOrderByIncrementId($contentObj->getOrderid());

        if ($order->getId()) {
            return $result->setHttpResponseCode(406)->setData(['error' => 'Order not found']);
        }

        $order->addCommentToStatusHistory('Billmate callback received', false);
        $this->orderUtil->saveOrder($order);
        return $result->setHttpResponseCode(200);
    }
}
