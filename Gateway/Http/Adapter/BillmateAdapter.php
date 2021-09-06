<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Adapter;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Model\Api\Client\Request\Factory as RequestFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ArticleFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article\DiscountsHandler;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response\PaymentInfoFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response\PaymentInfo;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Magento\Framework\HTTP\AsyncClient\GuzzleAsyncClientFactory;
use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\HTTP\AsyncClient\HttpException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

class BillmateAdapter
{
    const API_ENDPOINT = 'https://api.billmate.se/';
    const CREDENTIALS_CLIENT_ID = 'Magento2:Billmate_NwtBillmateCheckout:1.0';
    const DEFAULT_API_LANGUAGE = 'en';

    const FUNCTION_INIT_CHECKOUT = 'initCheckout';
    const FUNCTION_UPDATE_CHECKOUT = 'updateCheckout';
    const FUNCTION_GET_PAYMENTINFO = 'getPaymentinfo';

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
     * @throws LocalizedException
     *
     * @return DataObject Contains 'url' (for iframe) and 'number' (payment number)
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
            'callbackurl' => $this->url->getUrl('billmate/checkout/callback'),
            'returnmethod' => 'GET'
        ];

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

        $result = $this->post(self::FUNCTION_INIT_CHECKOUT, $this->dataUtil->createDataObject($data));
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Perform an updateCheckout call to the Billmate API
     *
     * @param Quote $quote
     * @throws LocalizedException
     * @throws HttpException
     *
     * @return DataObject Contains 'url' (for iframe) and 'number' (payment number)
     */
    public function updateCheckout($quote)
    {
        $data = [
            'Articles' => $this->generateArticles($quote),
            'Cart' => $this->generateCart($quote),
            'PaymentData' => [
                'number' => $quote->getPayment()->getAdditionalInformation('billmate_payment_number')
            ]
        ];
        $result = $this->post(self::FUNCTION_UPDATE_CHECKOUT, $this->dataUtil->createDataObject($data));
        return $this->dataUtil->createDataObject($result->getData('data'));
    }

    /**
     * Perform a getPaymentinfo call
     *
     * @param string $number Subject payment number
     * @return PaymentInfo
     * @throws LocalizedException
     * @throws HttpException
     */
    public function getPaymentInfo($number)
    {
        $data = [
            'number' => $number
        ];
        $result = $this->post(self::FUNCTION_GET_PAYMENTINFO, $this->dataUtil->createDataObject($data));
        return $this->paymentInfoFactory->create()->populateWithApiResponse($result['data']);
    }

    /**
     * @param array $params
     * @return string
     */
    private function buildEndpoint($params = [])
    {
        $query = '';
        $apiEndpoint = self::API_ENDPOINT;
        if (!empty($params)) {
            $query =  http_build_query($params);
        }

        return $apiEndpoint . $query;
    }

    /**
     * Perform a post request
     *
     * @param string $function
     * @param DataObject $data Data to add to the "Data" key of the structure
     * @throws LocalizedException
     * @throws HttpException
     *
     * @return DataObject
     */
    private function post(string $function, DataObject $data)
    {
        $credentials = [
            'key' => $this->config->getSecretKey(),
            'id' => $this->config->getMerchantAccountId(),
            'hash' => $this->dataUtil->hash($this->dataUtil->serialize($data)),
            'version' => $this->config->getApiVersion(),
            'client' => self::CREDENTIALS_CLIENT_ID,
            'language' => self::DEFAULT_API_LANGUAGE,
            'time' => (string)microtime(true),
            'test' => $this->config->getTestMode(),
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
            $this->buildEndpoint(),
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
            throw new LocalizedException(__($decodedResponse->getMessage()), null, $decodedResponse->getCode());
        }

        throw new LocalizedException(__('Invalid hash from response'));
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
        foreach ($quote->getAllItems() as $quoteItem) {
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

        $discountTaxComp = $quote->getShippingAddress()->getDiscountTaxCompensationAmount();
        $discountAmount = $quote->getShippingAddress()->getDiscountAmount();
        $withoutTax = $quote->getSubtotal() + $discountAmount + $discountTaxComp;
        $taxAmount = $quote->getShippingAddress()->getTaxAmount();
        $total = [
            'withouttax' => (int)100 * $withoutTax,
            'tax' => (int)100 * $taxAmount,
            'withtax' => (int)100 * $quote->getGrandTotal()
        ];

        if (!$quote->isVirtual()) {
            $shippingInclTax = (int)100 * $quote->getShippingAddress()->getShippingInclTax();

            if ($shippingInclTax > 0) {
                $shippingTaxAmount = 100 * $quote->getShippingAddress()->getShippingTaxAmount();
                $shippingExclTax = (int)$shippingInclTax - $shippingTaxAmount;
                $shipping = [
                    'withouttax' => $shippingExclTax,
                    'taxrate' => (int)100 * $shippingTaxAmount / $shippingExclTax,
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
