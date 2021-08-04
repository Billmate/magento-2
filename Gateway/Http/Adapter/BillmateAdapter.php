<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Adapter;

use Magento\Framework\HTTP\AsyncClient\GuzzleAsyncClientFactory;
use Magento\Framework\HTTP\AsyncClient\Request;
use Billmate\NwtBillmateCheckout\Model\Api\Client\Request\Factory as RequestFactory;
use Magento\Framework\HTTP\AsyncClient\HttpException;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\Serialize\SerializerInterface;

class BillmateAdapter
{
    const API_ENDPOINT = 'https://api.billmate.se/';
    const CREDENTIALS_CLIENT_ID = 'Magento2:Billmate_NwtBillmateCheckout:1.0';
    const DEFAULT_API_LANGUAGE = 'en';

    const FUNCTION_INIT_CHECKOUT = 'initCheckout';
    const FUNCTION_UPDATE_CHECKOUT = 'updateCheckout';

    /**
     * @var RequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var GuzzleAsyncClientFactory
     */
    private $httpClientFactory;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param GuzzleAsyncClientFactory $httpClientFactory
     * @param RequestFactory $httpRequestFactory
     * @param Config $config
     */
    public function __construct(
        GuzzleAsyncClientFactory $httpClientFactory,
        RequestFactory $httpRequestFactory,
        Config $config,
        UrlInterface $url,
        LocaleResolver $localeResolver,
        SerializerInterface $serializer
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->config = $config;
        $this->url = $url;
        $this->localeResolver = $localeResolver;
        $this->serializer = $serializer;
    }

    /**
     * Perform an initCheckout call to the Billmate API
     *
     * @param Quote $quote
     * @throws LocalizedException
     *
     * @return array Array containing 'url' (for iframe) and 'number' (payment number)
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
            'accepturl' => $this->url->getUrl('billmate/checkout/success'),
            'cancelurl' => $this->url->getUrl('checkout/cart'),
        ];

        $checkoutData = [
            'terms' => $this->config->getTermsUrl(),
            'privacyPolicy' => $this->config->getPrivacyPolicyUrl(),
            'companyview' => $this->config->getCompanyView(),
            'showphoneondelivery' => $this->config->getPhoneOnDelivery(),
            'redirectOnSuccess' => false
        ];

        $data = [
            'CheckoutData' => $checkoutData,
            'PaymentData' => $paymentData,
            'Articles' => $this->generateArticles($quote),
            'Cart' => $this->generateCart($quote)
        ];

        $result = $this->post(self::FUNCTION_INIT_CHECKOUT, $data);
        return $result['data'];
    }

    /**
     * Perform an updateCheckout call to the Billmate API
     *
     * @param Quote $quote
     * @throws LocalizedException
     *
     * @return array Array containing 'url' (for iframe) and 'number' (payment number)
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
        $result = $this->post(self::FUNCTION_UPDATE_CHECKOUT, $data);
        return $result['data'];
    }

    /**
     * @param array $params
     * @return string
     */
    protected function buildEndpoint($params = [])
    {
        $query = '';
        $apiEndpoint = self::API_ENDPOINT;
        if (!empty($params)) {
            $query =  http_build_query($params);
        }

        return $apiEndpoint . $query;
    }

    /**
     * @param $endpoint
     * @param array $options
     * @return string
     */
    protected function get()
    {
        $httpClient = $this->httpClientFactory->create();
        $request = $this->httpRequestFactory->create(
            $this->buildEndpoint(),
            Request::METHOD_GET,
            []
        );

        try {
            $response = $httpClient->request($request)->get();
            return $response->getBody();
        } catch (HttpException $e) {
            $exception = $this->handleException($e);
        }
    }

    /**
     * Perform a post request
     *
     * @param string $function
     * @param array $data Data to add to the "Data" key of the structure
     * @param array $additional Additional data to add to the top level of the structure
     * @throws LocalizedException
     *
     * @return array
     */
    private function post(string $function, array $data)
    {
        $credentials = [
            'key' => $this->config->getSecretKey(),
            'id' => $this->config->getMerchantAccountId(),
            'hash' => $this->hash($this->serializer->serialize($data)),
            'version' => $this->config->getApiVersion(),
            'client' => self::CREDENTIALS_CLIENT_ID,
            'language' => self::DEFAULT_API_LANGUAGE,
            'time' => (string)microtime(true),
            'test' => $this->config->getTestMode(),
        ];

        $requestBody = [
            'credentials' => $credentials,
            'data' => $data,
            'function' => $function
        ];

        $requestBody = $this->serializer->serialize($requestBody);

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
        try {
            $response = $httpClient->request($request)->get();
        } catch (HttpException $e) {
            $exception = $this->handleException($e);
        }

        $decodedResponse = $this->serializer->unserialize($response->getBody());
        if (isset($decodedResponse['credentials']) && $this->verifyHash($decodedResponse)) {
            return $decodedResponse;
        } elseif (isset($decodedResponse['code'])) {
            throw new LocalizedException(__($decodedResponse['message']), null, $decodedResponse['code']);
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
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $article['taxrate'] = (int)$quoteItem->getTaxPercent();
            $article['withouttax'] = (int)100 * ($quoteItem->getRowTotal() + $quoteItem->getWeeeTaxAppliedRowAmount());
            $article['artnr'] = $quoteItem->getSku();
            $article['title'] = $quoteItem->getName();
            $article['quantity'] = (int)$quoteItem->getQty();
            $article['aprice'] = (int)100 * $quoteItem->getPrice();
            $article['discount'] = $quoteItem->getDiscountPercent();

            $articles[] = $article;
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

        $total = [
            'withouttax' => (int)100 * $quote->getSubtotal(),
            'tax' => (int)100 * $quote->getShippingAddress()->getTaxAmount(),
            'withtax' => (int)100 * $quote->getGrandTotal()
        ];

        if (!$quote->isVirtual()) {
            $shippingAmountInclTax = (int)100 * $quote->getShippingAddress()->getShippingInclTax();

            if ($shippingAmountInclTax > 0) {
                $shippingTaxAmount = 100 * $quote->getShippingAddress()->getShippingTaxAmount();
                $shippingAmountExclTax = (int)$shippingAmountInclTax - $shippingTaxAmount;
                $shipping = [
                    'withouttax' => $shippingAmountExclTax,
                    'taxrate' => (int)100 * $shippingTaxAmount / $shippingAmountExclTax,
                    'withtax' => $shippingAmountInclTax,
                    'method' => $quote->getShippingAddress()->getShippingDescription(),
                    'method_code' => $quote->getShippingAddress()->getShippingMethod()
                ];
    
                $cart['Shipping'] = $shipping;
                $total['withouttax'] += $shippingAmountExclTax;
            }
        }

        $cart['Total'] = $total;
        return $cart;
    }

    /**
     * @param $mixed
     *
     * @return mixed
     */
    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }

        return $mixed;
    }

    /**
     * @param \Exception $e
     */
    private function handleException(\Exception $e)
    {
        // @todo Create ClientException that handles Billmate error codes
        return;
    }

    /**
     * Verify the hash from response
     *
     * @param array $responseArray
     * @return bool
     */
    private function verifyHash($responseArray)
    {
        $providedHash = $responseArray['credentials']['hash'];
        $actualHash = $this->hash($this->serializer->serialize($responseArray['data']));

        return $providedHash === $actualHash;
    }

    /**
     * sha512 hash a value using merchant ID as key
     *
     * @param string $val
     * @return string
     */
    private function hash($val)
    {
        $key = $this->config->getSecretKey();
        return hash_hmac('sha512', $val, $key);
    }
}
