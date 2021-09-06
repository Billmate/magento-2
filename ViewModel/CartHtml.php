<?php

namespace Billmate\NwtBillmateCheckout\ViewModel;

use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\CartInterface;
use Billmate\NwtBillmateCheckout\Exception\CheckoutConfigException;

class CartHtml
{
    /**
     * @var PageFactory
     */
    private $pageResultFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CartInterface
     */
    private $cart;

    public function __construct(
        PageFactory $pageResultFactory,
        CheckoutSession $checkoutSession,
        CartInterface $cart
    ) {
        $this->pageResultFactory = $pageResultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
    }

    /**
     * Get html for current cart data
     *
     * @return string
     * @throws CheckoutConfigException
     */
    public function getCartHtml()
    {
        $page = $this->pageResultFactory->create();
        $page->addHandle('billmate_checkout_index');
        $page->getLayout()->getUpdate()->load();
        $block = $page->getLayout()->getBlock('checkout.cart.form');

        if (!$block instanceof AbstractCart) {
            throw new CheckoutConfigException(
                'Invalid layout configuration',
                CheckoutConfigException::CODE_LAYOUT_ERROR
            );
        }

        $block->setData('cart', $this->cart);
        $cartHtml = '';
        foreach ($this->checkoutSession->getQuote()->getAllVisibleItems() as $quoteItem) {
            $quoteItem->setBillmateProcess(true);
            $cartHtml .= $block->getItemHtml($quoteItem);
        }

        $cartItems = $this->cart->getItems();
        return $cartHtml;
    }
}
