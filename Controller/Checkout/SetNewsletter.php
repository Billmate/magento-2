<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\AbstractResult;

class SetNewsletter implements HttpPostActionInterface
{
    /**
     * @var ControllerUtil
     */
    private ControllerUtil $util;

    public function __construct(
        ControllerUtil $util
    ) {
        $this->util = $util;
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
            $subscribeStatus
        );

        return $this->util->jsonResult(['success' => true, 'subscribeStatus' => $subscribeStatus]);
    }
}