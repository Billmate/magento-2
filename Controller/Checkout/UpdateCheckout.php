<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;

class UpdateCheckout implements HttpGetActionInterface
{

    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var DataUtil
     */
    private $dataUtil;
    
    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    public function __construct(
        ControllerUtil $util,
        DataUtil $dataUtil,
        BillmateAdapter $billmateAdapter
    ) {
        $this->util = $util;
        $this->dataUtil = $dataUtil;
        $this->billmateAdapter = $billmateAdapter;
    }

    public function execute()
    {
        if (!$this->util->isAjax()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $checkoutSession = $this->util->getCheckoutSession();
        try {
            $this->billmateAdapter->updateCheckout($checkoutSession->getQuote());
        } catch (\Exception $e) {
            $paymentNumber = $checkoutSession->getBillmatePaymentNumber();
            $this->dataUtil->setContextPaymentNumber($paymentNumber);
            $this->dataUtil->logErrorMessage('[updateCheckout]' .  $e->getMessage());
            return $this->util->jsonResult(['success' => false, 'paymentNumber' => $paymentNumber]);
        }
        return $this->util->jsonResult(['success' => true]);
    }
}
