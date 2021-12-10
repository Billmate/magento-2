<?php

namespace Billmate\NwtBillmateCheckout\ViewModel;

use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\CartInterface;
use Billmate\NwtBillmateCheckout\Exception\CheckoutConfigException;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;

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

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        PageFactory $pageResultFactory,
        CheckoutSession $checkoutSession,
        CartInterface $cart,
        Config $config
    ) {
        $this->pageResultFactory = $pageResultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->config = $config;
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
        $twoColumnsLayout = ($this->config->getLayoutType() == '2columns-billmate');

        if (!$block instanceof AbstractCart) {
            throw new CheckoutConfigException(
                'Invalid layout configuration',
                CheckoutConfigException::CODE_LAYOUT_ERROR
            );
        }

        $block->setData('cart', $this->cart);
        $block->setData('twoColumnsLayout', $twoColumnsLayout);
        $cartHtml = '';
        foreach ($this->checkoutSession->getQuote()->getAllVisibleItems() as $quoteItem) {
            $quoteItem->setBillmateProcess(true);
            $quoteItem->setTwoColumnsLayout($twoColumnsLayout);
            $cartHtml .= $block->getItemHtml($quoteItem);
        }

        return $cartHtml;
    }
}
