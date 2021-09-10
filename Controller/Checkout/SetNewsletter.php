<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\AbstractResult;

class SetNewsletter implements HttpPostActionInterface
{
    /**
     * @var ControllerUtil
     */
    private ControllerUtil $util;

    /**
     * @var OrderUtil
     */
    private OrderUtil $orderUtil;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
    }

    /**
     * Sets newsletter preference as a session value, to be handled on order completion
     *
     * @return AbstractResult
     */
    public function execute(): AbstractResult
    {
        if (!$this->util->getRequest()->isPost() || !$this->util->validateFormKey()) {
            return $this->util->forwardNoRoute();
        }

        $subscribeStatus = $this->util->getRequest()->getParam('newsletter', false);
        $this->util->getCheckoutSession()->setData(
            'billmate_subscribe_newsletter',
            $this->util->getRequest()->getParam('newsletter', false)
        );

        return $this->util->jsonResult(['success' => true, 'subscribeStatus' => $subscribeStatus]);
    }
}