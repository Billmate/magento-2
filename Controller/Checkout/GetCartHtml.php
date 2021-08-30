<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Billmate\NwtBillmateCheckout\Exception\CheckoutConfigException;
use Billmate\NwtBillmateCheckout\ViewModel\CartHtml;

class GetCartHtml implements HttpGetActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var CartHtml
     */
    private $cartHtml;

    public function __construct(
        ControllerUtil $util,
        CartHtml $cartHtml
    ) {
        $this->util = $util;
        $this->cartHtml = $cartHtml;
    }

    public function execute()
    {
        if (!$this->util->isAjax()) {
            return $this->util->forwardNoRoute();
        }

        $resultData = ['success' => true];
        try {
            $resultData['carthtml'] = $this->cartHtml->getCartHtml();
        } catch (CheckoutConfigException $e) {
            $resultData = ['success' => false, 'message' => __('Billmate Checkout is incorrectly configured')];
        }

        return $this->util->jsonResult($resultData);
    }
}
