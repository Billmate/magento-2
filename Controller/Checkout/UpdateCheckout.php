<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;

class UpdateCheckout implements HttpGetActionInterface
{

    /**
     * @var ControllerUtil
     */
    private $util;
    
    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        ControllerUtil $util,
        BillmateAdapter $billmateAdapter,
        Session $checkoutSession
    ) {
        $this->util = $util;
        $this->billmateAdapter = $billmateAdapter;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        if (!$this->util->isAjax()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        // TODO error handling
        $this->billmateAdapter->updateCheckout($this->checkoutSession->getQuote());
        return $this->util->jsonResult(['success' => true]);
    }
}
