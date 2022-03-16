<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Request\DataBuilder;

/**
 * Builds Credentials for Billmate API transactions
 */
class CredentialsDataBuilder extends AbstractDataBuilder
{
    const KEY_BILLMATE_TEST_MODE = 'billmate_test_mode';

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $testMode = !!$paymentDO->getPayment()->getAdditionalInformation(self::KEY_BILLMATE_TEST_MODE);

        $result = [
            'credentials' => $this->dataObjectFactory->create()->setData([
                'id' => $this->config->getMerchantAccountId($storeId),
                'key' => $this->config->getSecretKey($storeId),
                'test' => $testMode
            ])
        ];

        return $result;
    }
}
