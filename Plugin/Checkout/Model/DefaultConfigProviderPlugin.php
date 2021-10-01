<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Model;

use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Ui\Component\Form\Element\Multiline;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressMetadataInterface;

class DefaultConfigProviderPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        CheckoutSession $checkoutSession,
        AddressMetaDataInterface $addressMetadata,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->addressMetadata = $addressMetadata;
        $this->config = $config;
    }

    /**
     * Change how default addresses are set in js config
     *
     * @param DefaultConfigProvider $subject
     * @param array $output
     * @return void
     */
    public function afterGetConfig($subject, $output)
    {
        if (!$this->config->getActive()) {
            return $output;
        }

        $quote = $this->checkoutSession->getQuote();
        $email = $quote->getShippingAddress()->getEmail();
        $shippingAddressFromData = $this->getAddressFromData($quote->getShippingAddress());
        $billingAddressFromData = $this->getAddressFromData($quote->getBillingAddress());
        $output['shippingAddressFromData'] = $shippingAddressFromData;
        $output['billingAddressFromData'] = $billingAddressFromData;
        $output['validatedEmailValue'] = $email;
        return $output;
    }

    /**
     * Create address data appropriate to fill checkout address form
     *
     * @param AddressInterface $address
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAddressFromData(AddressInterface $address)
    {
        $addressData = [];
        $attributesMetadata = $this->addressMetadata->getAllAttributesMetadata();
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            $attributeData = $address->getData($attributeCode);
            if ($attributeData) {
                if ($attributeMetadata->getFrontendInput() === Multiline::NAME) {
                    $attributeData = \is_array($attributeData) ? $attributeData : explode("\n", $attributeData);
                    $attributeData = (object)$attributeData;
                }
                if ($attributeMetadata->isUserDefined()) {
                    $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES][$attributeCode] = $attributeData;
                    continue;
                }
                $addressData[$attributeCode] = $attributeData;
            }
        }
        return $addressData;
    }
}
