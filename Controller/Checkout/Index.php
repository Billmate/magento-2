<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Quote\Model\Quote\Address;

class Index implements HttpGetActionInterface
{
    /**
     * @var ControllerUtil
     */
    private $util;

    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    /**
     * @var Session
     */
    private $checkoutSession;

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

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    public function __construct(
        ControllerUtil $util,
        BillmateAdapter $billmateAdapter,
        Session $checkoutSession,
        QuoteRepositoryInterface $quoteRepo,
        Config $config,
        TotalsCollector $totalsCollector,
        DirectoryHelper $directoryHelper
    ) {
        $this->util = $util;
        $this->billmateAdapter = $billmateAdapter;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepo = $quoteRepo;
        $this->config = $config;
        $this->totalsCollector = $totalsCollector;
        $this->directoryHelper = $directoryHelper;
    }

    public function execute()
    {
        if (!$this->config->getEnabled()) {
            return $this->util->forwardNoRoute();
        }

        $pageResult = $this->util->pageResult();
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this->util->redirect('checkout/cart');
        }

        $this->setQuoteDefaults($quote);
        $this->setPaymentMethod($quote);

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
        }

        $quotePaymentNumber = $quote->getPayment()->getAdditionalInformation('billmate_payment_number');

        if (true || !$quotePaymentNumber) {
            $initCheckoutData = $this->billmateAdapter->initCheckout($quote);
            $paymentNumber = $initCheckoutData->getNumber();
            $quote->getPayment()->setAdditionalInformation('billmate_payment_number', $paymentNumber);
            $this->checkoutSession->setData('billmate_iframe_url', $initCheckoutData->getUrl());
            $this->checkoutSession->setData('billmate_payment_number', $paymentNumber);
        } else {
            $updateCheckoutData = $this->billmateAdapter->updateCheckout($quote);
            $this->checkoutSession->setData('billmate_iframe_url', $updateCheckoutData->getUrl());
        }
        // TODO error handling

        $this->saveQuote($quote);
        return $pageResult;
    }

    /**
     * Save quote if relevant data has changed
     *
     * @param Quote $quote
     * @return void
     */
    private function saveQuote($quote)
    {
        $payment = $quote->getPayment();
        if ($quote->dataHasChangedFor('reserved_order_id') ||
            $payment->dataHasChangedFor('additional_information') ||
            $payment->dataHasChangedFor('method') ||
            $quote->dataHasChangedFor('shipping_address') ||
            $quote->getShippingAddress()->dataHasChangedFor('shipping_amount')) {
            $this->quoteRepo->save($quote);
        }
    }

    /**
     * Set payment method to Billmate
     *
     * @param Quote $quote
     * @return void
     */
    private function setPaymentMethod($quote)
    {
        $payment = $quote->getPayment();
        $paymentMethod = $payment->getMethod();

        if (!$paymentMethod ||
            $paymentMethod !== Config::METHOD_CODE
        ) {
            $payment->unsMethodInstance()->setMethod(Config::METHOD_CODE);
            $quote->setTotalsCollectedFlag(false);
        }
    }

    /**
     * Set default and placeholder values
     *
     * @param Quote $quote
     * @return void
     */
    private function setQuoteDefaults($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $this->setAddressDefaults($billingAddress);
        $this->setAddressDefaults($shippingAddress);

        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        if (!$shippingAddress->getShippingMethod()) {
            $shippingRates = $shippingAddress->getGroupedAllShippingRates();
            $defShippingMethod = $this->config->getDefaultShippingMethod();
    
            $methods = [];
            // Set a default shipping method
            foreach ($shippingRates as $carrierRates) {
                foreach ($carrierRates as $rate) {
                    $methods[$rate->getCode()] = $rate;
                }
            }
    
            $firstAvailable = current($methods);
            if (!empty($methods)) {
                if (isset($methods[$defShippingMethod])) {
                    $shippingMethodCode = $methods[$defShippingMethod]->getCode();
                } else {
                    $shippingMethodCode = $firstAvailable->getCode();
                }
            }
    
            $shippingAddress->setShippingMethod($shippingMethodCode);
        }
        $shippingAddress->collectShippingRates();
        $quote->collectTotals();
    }

    /**
     * Set default and placeholder values if necessary
     *
     * @param Address $address
     * @return void
     */
    private function setAddressDefaults($address)
    {
        $allowedCountries = $this->directoryHelper->getCountryCollection()->toOptionArray();
        $country = $address->getCountryId();

        // Postcode and country is necessary for initial shipping calculation
        if (!$address->getCountry() ||
            !$address->getPostcode() ||
            !in_array($country, array_column($allowedCountries, 'value'))) {

            $country = $this->config->getDefaultCountry();
            $postcode = $this->config->getDefaultPostcode();

            $addressData = [
                'firstname' => '-',
                'lastname' => '-',
                'street1' => '-',
                'country_id' => $country,
                'postcode' => $postcode,
            ];

            $address->addData($addressData);
        }
    }
}
