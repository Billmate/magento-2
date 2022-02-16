<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Controller\Cart\Index;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;

class IndexPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    public function __construct(
        CheckoutSession $checkoutSession,
        OrderUtil $orderUtil,
        RedirectFactory $redirectFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderUtil = $orderUtil;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * If 'reserved' parameter is included, set that quote as the session's quote and reload page
     *
     * @param Index $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(Index $subject, ResultInterface $result)
    {
        $reservedOrderId = $subject->getRequest()->getParam('reserved');

        if (null === $reservedOrderId) {
            return $result;
        }

        $quote = $this->orderUtil->getQuoteByReservedOrderId($reservedOrderId);
        if (null === $quote || !$quote->getIsActive()) {
            return $result;
        }

        $this->checkoutSession->setQuoteId($quote->getId());
        return $this->redirectFactory->create()->setPath('*/*');
    }
}
