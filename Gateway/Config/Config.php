<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Payment method code also used as part of payment config path:
     * payment/{method_code}/{field}
     */
    const METHOD_CODE = 'nwt_billmate';

    /**
     * Group keys
     */
    const GROUP_GENERAL = 'general';

    const GROUP_DESIGN = 'design';

    /**
     * Field keys
     */
    const KEY_ENABLED = 'enabled';

    const KEY_TEST_MODE = 'testmode';

    const KEY_MERCHANT_ID = 'merchant_id';

    const KEY_API_VERSION = 'api_version';

    const KEY_SECRET_KEY = 'secret_key';

    const KEY_TERMS_URL = 'terms_url';

    const KEY_PRIVACY_POLICY_URL = 'privacy_policy_url';

    const KEY_COMPANY_VIEW = 'company_view';

    const KEY_PHONE_ON_DELIVERY = 'phone_on_delivery';

    const KEY_DEFAULT_COUNTRY = 'default_country';

    const KEY_DEFAULT_POSTCODE = 'default_postcode';

    const KEY_DEFAULT_SHIPPINGMETHOD = 'default_shippingmethod';

    const KEY_ENABLE_INVOICE_FEE = 'enable_invoice_fee';

    const KEY_INVOICE_FEE_AMOUNT = 'invoice_fee_amount';

    const KEY_ENABLE_ADDITIONAL_BLOCK = 'enable_additional_block';

    const KEY_ADDITIONAL_BLOCK = 'additional_block';

    /**
     * Mapping of payment method IDs to descriptors
     * @link https://billmate.github.io/api-docs/#getpaymentinfo - See Response Body -> PaymentData -> method
     */
    const PAYMENT_METHOD_MAPPING = [
        '1' => 'Invoice Factoring',
        '2' => 'Invoice Service',
        '4' => 'Invoice Part Payment',
        '8' => 'Card',
        '16' => 'Bank',
        '24' => 'Card/Bank',
        '32' => 'Cash (Receipt)'
    ];

    /**
     * Get Enabled flag
     *
     * @param integer $storeId
     * @return boolean
     */
    public function getEnabled(int $storeId = null): bool
    {
        return (bool)$this->getGeneralGroupValue(
            self::KEY_ENABLED,
            $storeId
        );
    }

    /**
     * Get Merchant account ID
     *
     * @param int $storeId
     * @return string
     */
    public function getMerchantAccountId(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_MERCHANT_ID,
            $storeId
        ) ?? '';
    }

    /**
     * Get Test mode flag
     *
     * @param int $storeId
     * @return bool
     */
    public function getTestMode(int $storeId = null): bool
    {
        return (bool)$this->getGeneralGroupValue(
            self::KEY_TEST_MODE,
            $storeId
        );
    }

    /**
     * Get API version
     *
     * @param int $storeId
     * @return string
     */
    public function getApiVersion(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_API_VERSION,
            $storeId
        ) ?? '';
    }

    /**
     * Get merchant account secret key
     *
     * @param integer $storeId
     * @return string
     */
    public function getSecretKey(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_SECRET_KEY,
            $storeId
        ) ?? '';
    }

    /**
     * Get URL to terms page
     *
     * @param integer $storeId
     * @return string
     */
    public function getTermsUrl(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_TERMS_URL,
            $storeId
        ) ?? '';
    }

    /**
     * Get URL to privacy policy page
     *
     * @param integer $storeId
     * @return string
     */
    public function getPrivacyPolicyUrl(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_PRIVACY_POLICY_URL,
            $storeId
        ) ?? '';
    }

    /**
     * Get Company view flag
     *
     * @param integer $storeId
     * @return bool
     */
    public function getCompanyView(int $storeId = null): bool
    {
        return (bool)$this->getGeneralGroupValue(
            self::KEY_COMPANY_VIEW,
            $storeId
        );
    }

    /**
     * Get Phone On Delivery flag
     *
     * @param integer $storeId
     * @return bool
     */
    public function getPhoneOnDelivery(int $storeId = null): bool
    {
        return (bool)$this->getGeneralGroupValue(
            self::KEY_PHONE_ON_DELIVERY,
            $storeId
        );
    }

    /**
     * Get default country
     *
     * @param integer $storeId
     * @return string
     */
    public function getDefaultCountry(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_DEFAULT_COUNTRY,
            $storeId
        ) ?? '';
    }

    /**
     * Get default country
     *
     * @param integer $storeId
     * @return string
     */
    public function getDefaultPostcode(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_DEFAULT_POSTCODE,
            $storeId
        ) ?? '';
    }

    /**
     * Get default shipping method
     *
     * @param integer $storeId
     * @return string
     */
    public function getDefaultShippingMethod(int $storeId = null): string
    {
        return $this->getGeneralGroupValue(
            self::KEY_DEFAULT_SHIPPINGMETHOD,
            $storeId
        ) ?? '';
    }

    /**
     * Get enable invoice fee flag
     *
     * @param integer $storeId
     * @return boolean
     */
    public function getEnableInvoiceFee(int $storeId): bool
    {
        return (bool)$this->getGeneralGroupValue(
            self::KEY_ENABLE_INVOICE_FEE,
            $storeId
        );
    }

    /**
     * Get invoice fee amount
     *
     * @param integer $storeId
     * @return float
     */
    public function getInvoiceFeeAmount(int $storeId): float
    {
        return $this->getGeneralGroupValue(
            self::KEY_INVOICE_FEE_AMOUNT,
            $storeId
        );
    }

    /**
     * Get enable additional block flag
     *
     * @param integer $storeId
     * @return boolean
     */
    public function getEnableAdditionalBlock(int $storeId = null): bool
    {
        return (bool)$this->getDesignGroupValue(
            self::KEY_ENABLE_ADDITIONAL_BLOCK,
            $storeId
        );
    }

    /**
     * Get additional block code
     *
     * @param integer $storeId
     * @return string
     */
    public function getAdditionalBlock(int $storeId = null): string
    {
        return $this->getDesignGroupValue(
            self::KEY_ADDITIONAL_BLOCK,
            $storeId
        ) ?? '';
    }

    /**
     * Use for fields in the general group
     *
     * @param string $field
     * @param integer $storeId
     * @return mixed
     */
    private function getGeneralGroupValue(string $field, int $storeId = null)
    {
        return $this->getValue(
            self::GROUP_GENERAL . '/' . $field,
            $storeId
        );
    }

    /**
     * Use for fields in the design group
     *
     * @param string $field
     * @param integer $storeId
     * @return mixed
     */
    private function getDesignGroupValue(string $field, int $storeId = null)
    {
        return $this->getValue(
            self::GROUP_DESIGN . '/' . $field,
            $storeId
        );
    }
}
