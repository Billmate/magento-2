<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Adapter;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Model\Api\Client\Request\Factory as RequestFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response\PaymentInfoFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response\PaymentInfo;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Helper\CentsFormatter;
use Magento\Framework\HTTP\AsyncClient\GuzzleAsyncClientFactory;
use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\HTTP\AsyncClient\HttpException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Framework\DataObject;

class BillmateAdapter
{
    const API_ENDPOINT = 'https://api.billmate.se/';
    const CREDENTIALS_CLIENT_ID = 'Magento2:Billmate_NwtBillmateCheckout:1.0';
    const DEFAULT_API_LANGUAGE = 'en';

    const FUNCTION_INIT_CHECKOUT = 'initCheckout';
    const FUNCTION_UPDATE_CHECKOUT = 'updateCheckout';
    const FUNCTION_GET_PAYMENTINFO = 'getPaymentinfo';
    const FUNCTION_ACTIVATE_PAYMENT = 'activatePayment';
    const FUNCTION_CREDIT_PAYMENT = 'creditPayment';
    const FUNCTION_CANCEL_PAYMENT = 'cancelPayment';

    use CentsFormatter;

    /**
     * @var RequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var GuzzleAsyncClientFactory
     */
    private $httpClientFactory;

    /**
     * @var DataUtil
     */
    private $dataUtil;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * @var ArticleFactory
     */
    private $articleFactory;

    /**
     * @var DiscountsHandler
     */
    private $discountsHandler;

    /**
     * @var PaymentInfoFactory
     */
    private $paymentInfoFactory;

    /**
     * @param GuzzleAsyncClientFactory $httpClientFactory
     * @param RequestFactory $httpRequestFactory
     * @param Config $config
     */
    public function __construct(
        GuzzleAsyncClientFactory $httpClientFactory,
        RequestFactory $httpRequestFactory,
        DataUtil $dataUtil,
        Config $config,
        UrlInterface $url,
        LocaleResolver $localeResolver,
        ArticleFactory $articleFactory,
        DiscountsHandler $discountsHandler,
        PaymentInfoFactory $paymentInfoFactory
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->dataUtil = $dataUtil;
        $this->config = $config;
        $this->url = $url;
        $this->localeResolver = $localeResolver;
        $this->articleFactory = $articleFactory;
        $this->discountsHandler = $discountsHandler;
        $this->paymentInfoFactory = $paymentInfoFactory;
    }

    /**
     * Perform an initCheckout call to the Billmate API
     *
     * @param Quote $quote
     * @throws ClientException
     *
     * @return DataObject Contains 'url' (for iframe) and 'number' (payment number)
     * @throws ClientException
     * @throws HttpException
     */
    public function initCheckout($quote)
    {
        $localeCode = $this->localeResolver->getLocale();
        $language = strstr($localeCode, '_', true);
        $country = str_replace('_', '', strstr($localeCode, '_'));

        $paymentData = [
            'currency' => $quote->getQuoteCurrencyCode(),
            'language' => $language,
            'country' => $country,
            'orderid' => $quote->getReservedOrderId(),
            'accepturl' => $this->url->getUrl('billmate/checkout/confirmorder'),
            'cancelurl' => $this->url->getUrl('checkout/cart'),
        ];

        // Callback URL can use a dev setting
        $callbackUrl = $this->url->getUrl('billmate/checkout/callback');
        $devCallbackDomain = $this->config->getCallbackDomain();
        if ($devCallbackDomain) {
            $callbackUrl = $devCallbackDomain . 'billmate/checkout/callback';
        }
        $paymentData['callbackurl'] = $callbackUrl;

        $checkoutData = [
            'terms' => $this->config->getTermsUrl(),
            'privacyPolicy' => $this->config->getPrivacyPolicyUrl(),
            'companyView' => ($this->config->getCompanyView()) ? 'true' : 'false',
            'showPhoneOnDelivery' => ($this->config->getPhoneOnDelivery()) ? 'true' : 'false',
            'redirectOnSuccess' => false
        ];

        $data = [
            'CheckoutData' => $checkoutData,
            'PaymentData' => $paymentData,
            'Articles' => $this->generateArticles($quote),
            'Cart' => $this->generateCart($quote)
        ];

        $result = $this->post(
            self::FUNCTION_INIT_CHECKOUT,
            $this->dataUtil->createDataObject($data),
            $this->getCurrentStoreCredentials()
        );
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Perform an updateCheckout call to the Billmate API
     *
     * @param Quote $quote
     * @throws ClientException
     * @throws HttpException
     *
     * @return DataObject Contains 'url' (for iframe) and 'number' (payment number)
     */
    public function updateCheckout(Quote $quote)
    {
        $data = [
            'Articles' => $this->generateArticles($quote),
            'Cart' => $this->generateCart($quote),
            'PaymentData' => [
                'number' => $quote->getPayment()->getAdditionalInformation('billmate_payment_number')
            ]
        ];
        $result = $this->post(
            self::FUNCTION_UPDATE_CHECKOUT,
            $this->dataUtil->createDataObject($data),
            $this->getCurrentStoreCredentials()
        );
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Perform a getPaymentinfo call
     *
     * @param string $number Subject payment number
     * @param DataObject $credentials Object containing secret key, merchant ID, and test mode flag
     * @return PaymentInfo
     * @throws ClientException
     * @throws HttpException
     */
    public function getPaymentInfo(string $number, DataObject $credentials = null)
    {
        $credentials = $credentials ?? $this->getCurrentStoreCredentials();
        $data = [
            'number' => $number
        ];

        $result = $this->post(
            self::FUNCTION_GET_PAYMENTINFO,
            $this->dataUtil->createDataObject($data),
            $credentials
        );
        return $this->paymentInfoFactory->create()->populateWithApiResponse($result['data']);
    }

    /**
     * Activate (capture) a payment
     *
     * @param string $number Invoice number of payment to activate
     * @param DataObject $credentials Object containing secret key, merchant ID, and test mode flag
     * @throws ClientException
     * @throws HttpException
     * @return DataObject
     */
    public function activatePayment(string $number, DataObject $credentials): DataObject
    {
        $data = [
            'number' => $number
        ];
        $result = $this->post(
            self::FUNCTION_ACTIVATE_PAYMENT,
            $this->dataUtil->createDataObject($data),
            $credentials
        );
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Cancel a payment
     *
     * @param string $number Invoice number of payment to activate
     * @param DataObject $credentials Object containing secret key, merchant ID, and test mode flag
     * @throws ClientException
     * @throws HttpException
     * @return DataObject
     */
    public function cancelPayment(string $number, DataObject $credentials): DataObject
    {
        $data = [
            'number' => $number
        ];
        $result = $this->post(
            self::FUNCTION_CANCEL_PAYMENT,
            $this->dataUtil->createDataObject($data),
            $credentials
        );
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Credit (refund) a payment
     * @link https://billmate.github.io/api-docs/#creditpayment
     *
     * @param DataObject $data
     * @param DataObject $credentials Object containing secret key, merchant ID, and test mode flag
     * @return DataObject
     * @throws ClientException
     * @throws HttpException
     */
    public function creditPayment(DataObject $data, DataObject $credentials): DataObject
    {
        $result = $this->post(self::FUNCTION_CREDIT_PAYMENT, $data, $credentials);
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Get credentials of current store
     *
     * @return DataObject
     */
    private function getCurrentStoreCredentials(): DataObject
    {
        return $this->dataUtil->createDataObject([
            'id' => $this->config->getMerchantAccountId(),
            'key' => $this->config->getSecretKey(),
            'test' => $this->config->getTestMode()
        ]);
    }

    /**
     * Perform a post request
     *
     * @param string $function
     * @param DataObject $data Data to add to the "Data" key of the structure
     * @param DataObject $credentials Object containing secret key, merchant ID, and test mode flag
     * @throws ClientException
     * @throws HttpException
     *
     * @return DataObject
     */
    private function post(string $function, DataObject $data, DataObject $credentials)
    {
        $credentials = [
            'key' => $credentials->getKey(),
            'id' => $credentials->getId(),
            'hash' => $this->dataUtil->hash($this->dataUtil->serialize($data)),
            'version' => $this->config->getApiVersion(),
            'client' => self::CREDENTIALS_CLIENT_ID,
            'language' => self::DEFAULT_API_LANGUAGE,
            'time' => (string)microtime(true),
            'test' => $credentials->getTest() ? 'true' : 'false',
        ];

        $requestBody = $this->dataUtil->createDataObject([
            'credentials' => $credentials,
            'data' => $data->toArray(),
            'function' => $function
        ]);

        $requestBody = $this->dataUtil->serialize($requestBody);

        $requestHeader = [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($requestBody)
        ];

        $httpClient = $this->httpClientFactory->create();
        $request = $this->httpRequestFactory->create(
            self::API_ENDPOINT,
            Request::METHOD_POST,
            $requestHeader,
            $requestBody
        );

        $response = [];
        $response = $httpClient->request($request)->get();

        $decodedResponse = $this->dataUtil->unserialize($response->getBody());
        if (null !== $decodedResponse->getCredentials() && $this->dataUtil->verifyHash($decodedResponse)) {
            return $decodedResponse;
        } elseif (null !== $decodedResponse->getCode()) {
            throw new ClientException(__($decodedResponse->getMessage()), null, $decodedResponse->getCode());
        }

        throw new ClientException(__('Invalid hash from response'));
    }

    /**
     * Generate the Articles section for API calls
     *
     * @param Quote $quote
     * @return array
     */
    private function generateArticles($quote)
    {
        $articles = [];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            /** @var Item $quoteItem */
            if ($quoteItem->getProductType() === BundleType::TYPE_CODE) {
                foreach ($quoteItem->getChildren() as $bundleChildItem) {
                    $article = $this->articleFactory->create();
                    $article->initializeByQuoteItem($bundleChildItem);
                    $articles[] = $article->propertiesToArray();
                }
            }
            $article = $this->articleFactory->create();
            $article->initializeByQuoteItem($quoteItem);
            $articles[] = $article->propertiesToArray();
        }

        $discountArticles = $this->discountsHandler->toArticles();

        foreach ($discountArticles as $discountArticle) {
            $articles[] = $discountArticle->propertiesToArray();
        }

        return $articles;
    }

    /**
     * Generate the Cart section for API calls
     *
     * @param Quote $quote
     * @return array
     */
    private function generateCart($quote)
    {
        $cart = [];

        $calculationAddress = ($quote->isVirtual()) ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $discountTaxComp = $calculationAddress->getDiscountTaxCompensationAmount();
        $discountAmount = $calculationAddress->getDiscountAmount();
        $withoutTax = $quote->getSubtotal() + $discountAmount + $discountTaxComp;
        $taxAmount = $calculationAddress->getTaxAmount();
        $total = [
            'withouttax' => $this->toCents($withoutTax),
            'tax' => $this->toCents($taxAmount),
            'withtax' => $this->toCents($quote->getGrandTotal())
        ];

        if (!$quote->isVirtual()) {
            $shippingInclTax = $this->toCents($quote->getShippingAddress()->getShippingInclTax());

            if ($shippingInclTax > 0) {
                $shippingTaxAmount = $this->toCents($quote->getShippingAddress()->getShippingTaxAmount());
                $shippingExclTax = (int)$shippingInclTax - $shippingTaxAmount;
                $shipping = [
                    'withouttax' => $shippingExclTax,
                    'taxrate' => $this->toCents($shippingTaxAmount / $shippingExclTax),
                    'withtax' => $shippingInclTax,
                    'method' => $quote->getShippingAddress()->getShippingDescription(),
                    'method_code' => $quote->getShippingAddress()->getShippingMethod()
                ];
    
                $cart['Shipping'] = $shipping;
                $total['withouttax'] += $shippingExclTax;
            }
        }

        $cart['Total'] = $total;
        return $cart;
    }
}
