<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Controller\AbstractResult;
use Magento\Quote\Model\Quote\Address;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Customer\Model\Session;

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

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        ControllerUtil $util,
        BillmateAdapter $billmateAdapter,
        QuoteRepositoryInterface $quoteRepo,
        Config $config,
        TotalsCollector $totalsCollector,
        DirectoryHelper $directoryHelper,
        CheckoutHelper $checkoutHelper,
        Session $customerSession
    ) {
        $this->util = $util;
        $this->billmateAdapter = $billmateAdapter;
        $this->quoteRepo = $quoteRepo;
        $this->config = $config;
        $this->totalsCollector = $totalsCollector;
        $this->directoryHelper = $directoryHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * Initializes Quote and Billmate Checkout
     *
     * @return AbstractResult
     */
    public function execute()
    {
        if (!$this->config->getEnabled()) {
            return $this->util->forwardNoRoute();
        }

        $pageResult = $this->util->pageResult();
        $checkoutSession = $this->util->getCheckoutSession();
        $quote = $checkoutSession->getQuote();

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
            $checkoutSession->setData('billmate_iframe_url', $initCheckoutData->getUrl());
            $checkoutSession->setData('billmate_payment_number', $paymentNumber);
        } else {
            $updateCheckoutData = $this->billmateAdapter->updateCheckout($quote);
            $checkoutSession->setData('billmate_iframe_url', $updateCheckoutData->getUrl());
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
        $this->setCheckoutMethod();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $this->setAddressDefaults($billingAddress);
        $this->setAddressDefaults($shippingAddress);

        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        if (!$shippingAddress->getShippingMethod()) {
            $shippingRates = $shippingAddress->getGroupedAllShippingRates();
            $defShippingMethod = $this->config->getDefaultShippingMethod();
            $shippingMethodCode = null;
    
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

    /**
     * Sets appropriate checkout method value on quote
     *
     * @return void
     */
    private function setCheckoutMethod()
    {
        $checkoutSession = $this->util->getCheckoutSession();
        $quote = $checkoutSession->getQuote();
        if ($this->customerSession->isLoggedIn()) {
            $quote->setCheckoutMethod(Onepage::METHOD_CUSTOMER);
            return;
        }

        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
                return;
            }
            $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
        };
    }
}
