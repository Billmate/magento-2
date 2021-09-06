<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;

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
     * Wrapper for Magento\Quote\Model\QuoteManagement::submit
     *
     * @param Quote $quote
     * @return Order Will be an empty object if order wasn't created
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function submitQuote($quote)
    {
        $result = $this->quoteManagement->submit($quote);

        if (!$result instanceof Order) {
            // Here we return empty Order object for consistent return value
            return $this->orderFactory->create();
        }

        return $result;
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
