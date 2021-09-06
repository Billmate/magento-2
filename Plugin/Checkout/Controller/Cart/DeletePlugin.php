<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

use Billmate\NwtBillmateCheckout\Exception\CheckoutConfigException;
use Magento\Checkout\Controller\Cart;
use Magento\Framework\Controller\ResultInterface;

class DeletePlugin extends AbstractPlugin
{
    /**
     * @var string
     */
    private $processedCartHtml;

    public function afterExecute(Cart $subject, ResultInterface $result)
    {
        try {
            $this->processedCartHtml= $this->cartHtml->getCartHtml();
        } catch (CheckoutConfigException $e) {
            $this->messageManager->addErrorMessage('Billmate Checkout is configured incorrectly');
        }

        return parent::afterExecute($subject, $result);
    }

    protected function constructSuccessResponse(): array
    {
        return ['success' => true, 'carthtml' => $this->processedCartHtml];
    }
}
