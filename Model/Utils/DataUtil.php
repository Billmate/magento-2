<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Magento\Framework\Serialize\SerializerInterface;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

/**
 * Utility class for handling loggind and various types of data transformations
 */
class DataUtil
{
    private SerializerInterface $serializer;

    private DataObjectFactory $dataObjectFactory;

    private Config $config;

    private LoggerInterface $logger;

    public function __construct(
        SerializerInterface $serializer,
        DataObjectFactory $dataObjectFactory,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Serialize data to JSON
     *
     * @param DataObject $data
     * @return string
     * @throws \InvalidArgumentException
     */
    public function serialize(DataObject $data): string
    {
        $result = $this->serializer->serialize($data->toArray());
        return ($result) ?? '';
    }

    /**
     * Unserialize JSON data
     *
     * @param string $json
     * @return DataObject
     * @throws \InvalidArgumentException
     */
    public function unserialize(string $json): DataObject
    {
        $result = $this->serializer->unserialize($json);
        if (!is_array($result)) {
            $result = [$result];
        }
        return $this->createDataObject($result);
    }

    /**
     * Create a DataObject
     *
     * @param array $data
     * @return DataObject
     */
    public function createDataObject(array $data = []): DataObject
    {
        return $this->dataObjectFactory->create()->setData($data);
    }

    /**
     * Verify the hash from response
     *
     * @param DataObject $response The full response from Billmate to verify, as a DataObject
     * @return bool
     */
    public function verifyHash($response)
    {
        $credentialsArray = $response->getCredentials();
        $providedHash = $credentialsArray['hash'];
        $responseDataContent = $response->getData('data');
        if ($responseDataContent instanceof DataObject) {
            $responseDataContent = $responseDataContent->toArray();
        }
        $actualHash = $this->hash($this->serializer->serialize($responseDataContent));

        return $providedHash === $actualHash;
    }

    /**
     * sha512 hash a value using merchant ID as key
     *
     * @param string $val
     * @return string
     */
    public function hash($val)
    {
        $key = $this->config->getSecretKey();
        return hash_hmac('sha512', $val, $key);
    }

    /**
     * Get Config
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
