<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Magento\Framework\Serialize\SerializerInterface;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Billmate\NwtBillmateCheckout\Model\Utils\ErrorUtil;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DataObject;

/**
 * Utility class for handling logging and various types of data transformations
 */
class DataUtil
{
    private SerializerInterface $serializer;

    private DataObjectFactory $dataObjectFactory;

    private Config $config;

    private ErrorUtil $errorUtil;

    private ?string $contextPaymentNumber;

    public function __construct(
        SerializerInterface $serializer,
        DataObjectFactory $dataObjectFactory,
        Config $config,
        ErrorUtil $errorUtil
    ) {
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->config = $config;
        $this->errorUtil = $errorUtil;
        $this->contextPaymentNumber = null;
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
     * Display an error message in frontend
     *
     * @param string $message
     * @return void
     */
    public function displayErrorMessage(string $message): void
    {
        $this->errorUtil->errorMessage($message);
    }

    /**
     * If in test mode, display an exception message for debugging purposes.
     * If not in test mode, a more generic error message will instead be displayed.
     * In either case, the exception message is logged.
     *
     * @param \Exception $exception
     * @param string $alternativeMsg Optional alternative message to display in production mode. Will use a default msg if not provided.
     * @return void
     */
    public function displayExceptionMessage(\Exception $exception, string $alternativeMsg = null): void 
    {
        $logMessage = $exception->getMessage();
        $this->logErrorMessage($logMessage);
        if ($this->config->getTestMode()) {
            $this->errorUtil->exceptionMessage($exception);
            return;
        }

        $defaultAltMsg = $this->config->getDefaultErrorMessage();
        $altMsgToShow = $alternativeMsg ? $alternativeMsg : $defaultAltMsg;
        $this->displayErrorMessage($altMsgToShow);
    }

    /**
     * Log an error message
     *
     * @param string $logMessage
     * @return void
     */
    public function logErrorMessage(string $logMessage): void
    {
        // Add Test mode status and Payment Number as prefix if one exists
        $messagePrefix = '';
        if ($this->config->getTestMode()) {
            $messagePrefix = '[Test]';
        }

        if ($this->contextPaymentNumber) {
            $messagePrefix .= sprintf('[%s]', $this->contextPaymentNumber);
        }
        $logMessage = $messagePrefix . ' ' . $logMessage;
        $this->errorUtil->getLogger()->error($logMessage);
    }

    /**
     * Set context payment ID
     *
     * @param string $paymentNumber
     * @return void
     */
    public function setContextPaymentNumber($paymentNumber): void
    {
        $this->contextPaymentNumber = $paymentNumber;
    }
}
