<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Newsletter\Model\SubscriptionManager;

/**
 * Callback will search for created order (created by either by SuccessEvent or Confirmorder),
 * and perform the Authorize operation
 */
class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private ControllerUtil $util;

    private OrderUtil $orderUtil;

    private DataUtil $dataUtil;

    private SubscriptionManager $subscriptionManager;

    /**
     * Request content as a DataObject
     *
     * @var DataObject
     */
    private DataObject $requestContent;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        DataUtil $dataUtil,
        SubscriptionManager $subscriptionManager
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->dataUtil = $dataUtil;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function execute()
    {
        $result = $this->util->jsonResult();
        $orderId = $this->requestContent->getDataByPath('data/orderid');
        if (!is_numeric($orderId)) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Invalid order id']);
        }

        $order = $this->orderUtil->loadOrderByIncrementId($orderId);

        if (!$order->getId()) {
            return $result->setHttpResponseCode(406)->setData(['error' => 'Order not found']);
        }

        $invoiceNumber = $this->requestContent->getDataByPath('data/number');
        $order->getPayment()->setAdditionalInformation(ResponseValidator::KEY_INVOICE_NUMBER, $invoiceNumber);
        $this->orderUtil->authorizePayment($order);

        if ($order->getPayment()->getAdditionalInformation('billmate_subscribe_newsletter')) {
            $this->subscriptionManager->subscribe($order->getCustomerEmail(), $order->getStore()->getWebsiteId());
        }

        return $result->setHttpResponseCode(200);
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
            $this->dataUtil->logErrorMessage('Callback: Received invalid request data');
            return false;
        }

        $this->dataUtil->setContextPaymentNumber($this->requestContent->getDataByPath('data/number'));
        if (!$this->dataUtil->verifyHash($this->requestContent)) {
            $this->dataUtil->logErrorMessage('Callback: Invalid hash in request from Billmate');
            return false;
        }

        return true;
    }

    /**
     * Set request content property
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function extractContent(): void
    {
        $this->requestContent = $this->dataUtil->unserialize($this->util->getRequest()->getContent());
    }
}
