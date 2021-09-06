<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\PaymentData;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Customer;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\ResponseArticle;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DataObject;

/**
 * Class representing response from GetPaymentInfo
 * @link https://billmate.github.io/api-docs/#getpaymentinfo
 *
 * @method PaymentData getPaymentData()
 * @method Customer getCustomer()
 * @method ResponseArticle[] getArticles()
 * @method Cart getCart()
 */
class PaymentInfo extends DataObject
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($data);
    }

    /**
     * Populate object with response from API call GetPaymentInfo
     * @link https://billmate.github.io/api-docs/#getpaymentinfo
     *
     * @param array $data
     * @return $this
     */
    public function populateWithApiResponse($data): self
    {
        $factory = $this->dataObjectFactory;
        $this->populatePaymentData($factory->create()->setData($data['PaymentData']));
        $this->populateCustomer($factory->create()->setData($data['Customer']));
        $this->populateCart($factory->create()->setData($data['Cart']));
        $this->populateArticles($data['Articles']);
        return $this;
    }

    /**
     * Populate the PaymentData section
     *
     * @param DataObject $paymentData
     * @return void
     */
    private function populatePaymentData(DataObject $paymentData)
    {
        $this->setPaymentData($paymentData);
    }

    /**
     * Populate the Articles section
     *
     * @param array $articlesData
     * @return void
     */
    private function populateArticles($articlesData)
    {
        $articles = [];
        foreach ($articlesData as $articleData) {
            $article = $this->dataObjectFactory->create();
            $article->setData($articleData);
            $articles[] = $article;
        }
        $this->setArticles($articles);
    }

    /**
     * Populate the Customer section
     *
     * @param DataObject $customerData
     * @return void
     */
    private function populateCustomer(DataObject $customerData)
    {
        $billingData = $customerData->getData('Billing');
        $shippingData = $customerData->getData('Shipping');

        $customer = $this->dataObjectFactory->create();
        $customer->setNr($customerData->getData('nr'));
        $customer->setPno($customerData->getData('pno'));

        $billing = $this->dataObjectFactory->create()->setData($billingData);
        $shipping = $this->dataObjectFactory->create()->setData($shippingData);
        $customer->setBilling($billing);
        $customer->setShipping($shipping);
        $this->setCustomer($customer);
    }

    /**
     * Populate the Cart section
     *
     * @param DataObject $cartData
     * @return void
     */
    private function populateCart(DataObject $cartData)
    {
        $handlingData = $cartData->getData('Handling');
        $shippingData = $cartData->getData('Shipping');
        $totalData = $cartData->getData('Total');

        $cart = $this->dataObjectFactory->create();
        $handling = $this->dataObjectFactory->create()->setData($handlingData);
        $shipping = $this->dataObjectFactory->create()->setData($shippingData);
        $total = $this->dataObjectFactory->create()->setData($totalData);

        $cart->setHandling($handling);
        $cart->setShipping($shipping);
        $cart->setTotal($total);

        $this->setCart($cart);
    }
}
