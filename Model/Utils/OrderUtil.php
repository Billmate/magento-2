<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Billmate\NwtBillmateCheckout\Gateway\Validator\ResponseValidator;
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
use Magento\Sales\Model\Order\Payment;

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
     *
     * @param integer $quoteId
     * @return integer
     * @throws \Exception
     * @see \Magento\Quote\Model\QuoteManagement::placeOrder
     */
    public function placeOrder(int $quoteId): int
    {
        return $this->quoteManagement->placeOrder($quoteId);
    }

    /**
     * Authorizes order payment.
     * Creates and captures invoice if payment method is Swish.
     * Saves Order and Invoice.
     *
     * @param Order $order
     * @return void
     */
    public function authorizePayment(Order $order): void
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->authorize(true, $order->getBaseTotalDue());
        $payment->setAmountAuthorized($order->getTotalDue());

        $transactionSave = $this->transaction->addObject($order);
        $methodId = $payment->getAdditionalInformation(ResponseValidator::KEY_METHOD_ID);
        if (1024 === (int)$methodId) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->addComment('Payment was completed with Swish.');
            $transactionSave = $this->transaction->addObject($invoice);
            $this->invoiceSender->send($invoice);
        }

        $transactionSave->save();
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
