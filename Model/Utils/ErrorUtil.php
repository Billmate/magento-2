<?php

namespace Billmate\NwtBillmateCheckout\Model\Utils;

use Psr\Log\LoggerInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Utilty class for error message handling
 */
class ErrorUtil
{
    private LoggerInterface $logger;

    private ManagerInterface $messageManager;

    public function __construct(
        LoggerInterface $logger,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * Show an error message in frontend
     *
     * @param string $message
     * @return void
     */
    public function errorMessage(string $message): void
    {
        $this->messageManager->addErrorMessage($message);
    }

    /**
     * Show exception message in frontend. Use only in test mode.
     *
     * @param \Exception $e
     * @return void
     */
    public function exceptionMessage(\Exception $exception): void
    {
        $this->messageManager->addExceptionMessage($exception);
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