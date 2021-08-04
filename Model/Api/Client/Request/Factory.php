<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\Request;

/**
 * Custom Factory class for @see \Magento\Framework\HTTP\AsyncClient\Request
 */
class Factory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Framework\\HTTP\\AsyncClient\\Request')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string|null $body
     * @return \Magento\Framework\HTTP\AsyncClient\Request
     */
    public function create(string $url, string $method, array $headers, ?string $body = null)
    {
        $data = ['url' => $url, 'method' => $method, 'headers' => $headers, 'body' => $body];
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
