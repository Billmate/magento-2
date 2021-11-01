<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\Api\SearchCriteriaBuilder;

class OrderUtil
{
    private QuoteManagement $quoteManagement;

    private QuoteRepositoryInterface $quoteRepo;

    private OrderRepositoryInterface $orderRepo;

    private OrderResourceFactory $orderResourceFactory;

    private OrderFactory $orderFactory;

    private InvoiceService $invoiceService;

    private InvoiceSender $invoiceSender;

    private Transaction $transaction;

    private SearchCriteriaBuilder $criteriaBuilder;

    public function __construct(
        QuoteManagement $quoteManagement,
        QuoteRepositoryInterface $quoteRepo,
        OrderRepositoryInterface $orderRepo,
        OrderResourceFactory $orderResourceFactory,
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepo = $quoteRepo;
        $this->orderRepo = $orderRepo;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * Wrapper for Magento\Quote\Model\QuoteManagement::placeOrder,
     * also creates invoice if payment method is Swish
     *
     * @param integer $quoteId
     * @param string $paymentMethod
     * @return integer
     * @throws \Exception
     * @see \Magento\Quote\Model\QuoteManagement::placeOrder
     */
    public function placeOrder(int $quoteId, string $paymentMethod = null): int
    {
        $orderId = $this->quoteManagement->placeOrder($quoteId);
        if (strtolower($paymentMethod) !== 'swish') {
            return $orderId;
        }

        /** @var Order $order */
        $order = $this->orderRepo->get($orderId);
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $order
            ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->addCommentToStatusHistory('Swish Payment completed', true, true)
        ;
        $transactionSave = $this->transaction->addObject(
            $invoice
        )->addObject(
            $order
        );
        $transactionSave->save();
        $this->invoiceSender->send($invoice);
        return $orderId;
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

     /**
     * Get quote by Reserved Order Id
      *
      * @param string $reservedOrderId
      * @return CartInterface|null
      */
    public function getQuoteByReservedOrderId(string $reservedOrderId): ?CartInterface
    {
        $criteria = $this->criteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)->create();
        $result = $this->quoteRepo->getList($criteria)->getItems();
        return array_shift($result);
    }
}
