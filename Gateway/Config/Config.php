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

    const KEY_LAYOUT_TYPE = 'layout_type';

    /**
     * Get Enabled flag
     *
     * @param integer $storeId
     * @return boolean
     */
    public function getEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
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
        return $this->getValue(
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
        return (bool)$this->getValue(
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
        return $this->getValue(
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
        return $this->getValue(
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
        return $this->getValue(
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
        return $this->getValue(
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
        return (bool)$this->getValue(
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
        return (bool)$this->getValue(
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
        return $this->getValue(
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
        return $this->getValue(
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
        return $this->getValue(
            self::KEY_DEFAULT_SHIPPINGMETHOD,
            $storeId
        ) ?? '';
    }

    /**
     * Get layout type
     *
     * @param integer $storeId
     * @return string
     */
    public function getLayoutType(int $storeId = null): string
    {
        return $this->getValue(
            self::KEY_LAYOUT_TYPE,
            $storeId
        ) ?? '';
    }
}
