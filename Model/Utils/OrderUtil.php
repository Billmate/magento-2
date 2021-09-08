<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class OrderUtil
{
    private QuoteManagement $quoteManagement;

    private QuoteRepositoryInterface $quoteRepo;

    private OrderRepositoryInterface $orderRepo;

    private OrderResourceFactory $orderResourceFactory;

    private OrderFactory $orderFactory;

    public function __construct(
        QuoteManagement $quoteManagement,
        QuoteRepositoryInterface $quoteRepo,
        OrderRepositoryInterface $orderRepo,
        OrderResourceFactory $orderResourceFactory,
        OrderFactory $orderFactory
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepo = $quoteRepo;
        $this->orderRepo = $orderRepo;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Wrapper for Magento\Quote\Model\QuoteManagement::placeOrder
     *
     * @param integer $quoteId
     * @throws CouldNotSaveException
     * @return integer
     * @see \Magento\Quote\Model\QuoteManagement::placeOrder
     */
    public function placeOrder(int $quoteId): int
    {
        return $this->quoteManagement->placeOrder($quoteId);
    }

    /**
     * Load an order by increment ID
     *
     * @param string $incrementId
     * @return Order Will be an empty object if order with provided increment ID doesn't exist
     */
    public function loadOrderByIncrementId(string $incrementId): Order
    {
        $order = $this->orderFactory->create();
        $this->orderResourceFactory->create()->load(
            $order,
            $incrementId,
            'increment_id'
        );
        return $order;
    }

    /**
     * Save an order
     *
     * @param OrderInterface $order
     * @return void
     */
    public function saveOrder(OrderInterface $order): void
    {
        $this->orderRepo->save($order);
    }

    /**
     * Access quote repository
     *
     * @return QuoteRepositoryInterface
     */
    public function getQuoteRepository(): QuoteRepositoryInterface
    {
        return $this->quoteRepo;
    }
}
