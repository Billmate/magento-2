<?php

namespace Billmate\NwtBillmateCheckout\Controller\Processing;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
use Billmate\NwtBillmateCheckout\Model\Service\ReturnRequestData;
use Magento\Framework\App\RequestInterface;
use Magento\Newsletter\Model\SubscriptionManager;

/**
 * Callback will search for created order (created by either by SuccessEvent or Confirmorder),
 * and perform the Authorize operation
 */
class Callback extends ProcessingAbstract
{
    private BillmateAdapter $billmateAdapter;

    private SubscriptionManager $subscriptionManager;

    public function __construct(
        ControllerUtil $util,
        DataUtil $dataUtil,
        OrderUtil $orderUtil,
        ReturnRequestData $returnRequestData,
        BillmateAdapter $billmateAdapter,
        SubscriptionManager $subscriptionManager
    ) {
        parent::__construct($util, $dataUtil, $orderUtil, $returnRequestData);
        $this->billmateAdapter = $billmateAdapter;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function execute()
    {
        $result = $this->util->jsonResult();
        $requestContent = $this->returnRequestData->getRequestContent();
        $orderId = $requestContent->getDataByPath('data/orderid');
        if (null === $orderId) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Invalid order id']);
        }

        $invoiceNumber = $requestContent->getDataByPath('data/number');

        try {
            $paymentInfo = $this->billmateAdapter->getPaymentInfo($invoiceNumber);
            if ($paymentInfo->getPaymentData()->getStatus() === 'Cancelled') {
                return $result->setHttpResponseCode(200)->setData(
                    ['message' => 'Ignoring callback for cancelled payment']
                );
            }
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(404)->setData(
                ['error' => 'Unable to retrieve Billmate payment info']
            );
        }

        $order = $this->orderUtil->loadOrderByIncrementId($orderId);

        if (!$order->getId()) {
            return $result->setHttpResponseCode(406)->setData(['error' => 'Order not found']);
        }

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

    /**
     * Set request content property
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function extractContent(): void
    {
        $requestContent = $this->dataUtil->unserialize($this->util->getRequest()->getContent());
        $this->returnRequestData->setRequestContent($requestContent);
    }
}
