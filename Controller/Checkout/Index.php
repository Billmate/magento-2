<?php

namespace Billmate\NwtBillmateCheckout\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\QuoteValidationRules\MatchesPayment;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
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
     * @var DataUtil
     */
    private $dataUtil;

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
        DataUtil $dataUtil,
        TotalsCollector $totalsCollector,
        DirectoryHelper $directoryHelper,
        CheckoutHelper $checkoutHelper,
        Session $customerSession
    ) {
        $this->util = $util;
        $this->billmateAdapter = $billmateAdapter;
        $this->quoteRepo = $quoteRepo;
        $this->config = $config;
        $this->dataUtil = $dataUtil;
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
        if (!$this->config->getActive()) {
            return $this->util->forwardNoRoute();
        }

        $resultPage = $this->util->pageResult();
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

        try {
            $this->initOrUpdateCheckout($quote);
        } catch (\Exception $e) {
            $this->dataUtil->setContextPaymentNumber($quotePaymentNumber);
            $this->dataUtil->displayExceptionMessage($e);
            return $this->util->redirect('checkout/cart');
        }

        $this->saveQuote($quote);
        $layoutType = $this->config->getLayoutType();
        $resultPage->getConfig()->setPageLayout($layoutType);

        if ($layoutType == '2columns-billmate') {
            $resultPage->addHandle('billmate_checkout_2columns');
        }

        return $resultPage;
    }

    /**
     * Inits checkout if payment does not have Billmate payment number, or if currency has changed.
     * Otherwise it runs updateCheckout instead.
     *
     * @param Quote $quote
     * @return void
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Framework\HTTP\AsyncClient\HttpException
     */
    private function initOrUpdateCheckout(Quote $quote): void
    {
        $quotePaymentNumber = $quote->getPayment()->getAdditionalInformation('billmate_payment_number');

        if (!$quotePaymentNumber) {
            $this->initCheckout($quote);
            return;
        }

        $checkoutSession = $this->util->getCheckoutSession();
        if ($quote->getQuoteCurrencyCode() !== $checkoutSession->getData('billmate_payment_currency')) {
            $this->initCheckout($quote);
            return;
        }

        $this->billmateAdapter->updateCheckout($quote);
    }

    /**
     * Performs initCheckout API call and sets needed data in session and on quote
     *
     * @param Quote $quote
     * @return void
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Framework\HTTP\AsyncClient\HttpException
     */
    private function initCheckout(Quote $quote): void
    {
        $checkoutSession = $this->util->getCheckoutSession();
        $initCheckoutData = $this->billmateAdapter->initCheckout($quote);
        $paymentNumber = $initCheckoutData->getNumber();
        $quote->getPayment()->setAdditionalInformation('billmate_payment_number', $paymentNumber);
        $checkoutSession->setData('billmate_iframe_url', $initCheckoutData->getUrl());
        $checkoutSession->setData('billmate_payment_number', $paymentNumber);
        $checkoutSession->setData('billmate_payment_currency', $quote->getQuoteCurrencyCode());
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
            $this->setDefaultShippingMethod($shippingAddress);
        }

        $shippingAddress->collectShippingRates();
        $quote->collectTotals();
        $quote->getPayment()->unsAdditionalInformation(MatchesPayment::KEY_VALIDATE_PAYMENT_MATCH);
    }

    /**
     * Set a default shipping method
     *
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return void
     */
    private function setDefaultShippingMethod(\Magento\Quote\Model\Quote\Address $shippingAddress)
    {
        $shippingRates = $shippingAddress->getAllShippingRates();
        /** @var \Magento\Quote\Model\Quote\Address\Rate[] $rates */
        $defShippingMethod = $this->config->getDefaultShippingMethod();

        $methods = [];
        foreach ($shippingRates as $rate) {
            $methods[$rate->getCode()] = 1;
        }

        // Use configured default method if it's available. Else, use the first available method.
        if (!empty($methods)) {
            $firstAvailable = current($methods);
            $dueMethod = isset($methods[$defShippingMethod]) ? $defShippingMethod : $firstAvailable;
            $shippingAddress->setShippingMethod($dueMethod);
        }
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
