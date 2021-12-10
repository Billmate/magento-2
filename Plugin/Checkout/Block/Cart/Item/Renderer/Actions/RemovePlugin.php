<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Block\Cart\Item\Renderer\Actions;

use Magento\Checkout\Block\Cart\Item\Renderer\Actions\Remove;
use Magento\Framework\App\RequestInterface;

class RemovePlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * Force use of template from this module for remove cart item action on billmate checkout page
     *
     * @param Remove $subject
     * @return void
     */
    public function beforeToHtml(Remove $subject)
    {
        if ($this->request->getModuleName() === 'billmate' || $subject->getItem()->getBillmateProcess()) {
            $subject->setTemplate('Billmate_NwtBillmateCheckout::checkout/cart/item/remove.phtml');
        }
    }
}
