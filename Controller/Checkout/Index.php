<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Model\Quote\TotalsCollector;

class Index implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var ResultRedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepo;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    public function __construct(
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        BillmateAdapter $billmateAdapter,
        Session $checkoutSession,
        CustomerSession $customerSession,
        QuoteRepositoryInterface $quoteRepo,
        Config $config,
        TotalsCollector $totalsCollector
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->billmateAdapter = $billmateAdapter;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteRepo = $quoteRepo;
        $this->config = $config;
        $this->totalsCollector = $totalsCollector;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $this->setDefaultShipping($quote);

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
        }

        $quotePaymentNumber = $quote->getPayment()->getAdditionalInformation('billmate_payment_number');

        if (!$quotePaymentNumber) {
            $initCheckoutData = $this->billmateAdapter->initCheckout($quote);
            $quote->getPayment()->setAdditionalInformation('billmate_payment_number', $initCheckoutData['number']);
            $this->checkoutSession->setData('billmate_iframe_url', $initCheckoutData['url']);
            $this->checkoutSession->setData('billmate_payment_number', $initCheckoutData['number']);
        } else {
            $updateCheckoutData = $this->billmateAdapter->updateCheckout($quote);
            $this->checkoutSession->setData('billmate_iframe_url', $updateCheckoutData['url']);
        }

        $this->saveQuote($quote);
        return $resultPage;
    }

    /**
     * Save quote if relevant data has changed
     *
     * @param Quote $quote
     * @return void
     */
    private function saveQuote($quote)
    {
        if ($quote->dataHasChangedFor('reserved_order_id') ||
            $quote->getPayment()->dataHasChangedFor('additional_information') ||
            $quote->dataHasChangedFor('shipping_address') ||
            $quote->getShippingAddress()->dataHasChangedFor('shipping_amount')) {
            $this->quoteRepo->save($quote);
        }
    }

    /**
     * @param Quote $quote
     * @return void
     */
    private function setDefaultShipping($quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress->getShippingMethod()) {
            return;
        }

        $customer = $this->customerSession->getCustomerDataObject();
        if ($customer) {
            $quote->assignCustomer($customer);
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
        }

        if (!$shippingAddress->getCountry() && !$shippingAddress->getPostcode()) {

            // Set some default and placeholder values in address
            $country = $this->config->getDefaultCountry();
            $postcode = $this->config->getDefaultPostcode();

            $addressData = [
                'firstname' => '--',
                'lastname' => '--',
                'street' => '--',
                'city' => '--',
                'country_id' => $country,
                'postcode' => $postcode,
                'telephone' => '--'
            ];

            $quote->getBillingAddress()->addData($addressData);
            $shippingAddress->addData($addressData);
            $quote->setShippingAddress($shippingAddress);
        }

        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        $defShippingMethodCode = $this->config->getDefaultShippingMethod();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $methods[$rate->getCode()] = $rate;
            }
        }

        $firstAvailable = current($methods);
        $shippingMethodCode = $methods[$defShippingMethodCode] ?? $firstAvailable->getCode();
        if (!$shippingAddress->getShippingMethod() && $methods) {

            if (isset($methods[$defShippingMethodCode])) {
                $shippingMethodCode = $methods[$defShippingMethodCode];
            } else {
                $shippingMethodCode = $firstAvailable->getCode();
            }
        }

        $shippingAddress->setShippingMethod($shippingMethodCode);
        $shippingAddress->collectShippingRates();
        $quote->collectTotals();
    }
}
