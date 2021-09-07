<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Event\ManagerInterface;

class Success implements HttpGetActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    public function __construct(
        ControllerUtil $util,
        OrderUtil $orderUtil,
        ManagerInterface $eventManager
    ) {
        $this->util = $util;
        $this->orderUtil = $orderUtil;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        $session = $this->util->getCheckoutSession();
        $lastOrderId = $session->getLastOrderId();
        $session->clearQuote();

        $resultPage = $this->util->pageResult();

        $this->eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$lastOrderId]]
        );

        return $resultPage;
    }

    public function sessionIsValid()
    {
        if (!$this->util->getCheckoutSession()->getLastSuccessQuoteId()) {
            return false;
        }

        if (!$this->util->getCheckoutSession()->getLastOrderId()) {
            return false;
        }
        return true;
    }
}
