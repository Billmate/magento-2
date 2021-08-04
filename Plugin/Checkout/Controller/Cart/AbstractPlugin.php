<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Checkout\Controller\Cart as CartController;
use Magento\Framework\Message\Error;

abstract class AbstractPlugin
{

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    public function __construct(
        JsonFactory $jsonResultFactory,
        MessageManagerInterface $messageManager,
        CheckoutSession $checkoutSession
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Return a json result if it was an ajax request from billmate checkout
     *
     * @param CartController $subject
     * @param ResultInterface $result
     * @return void
     */
    public function afterExecute(CartController $subject, ResultInterface $result)
    {
        $billmate = $subject->getRequest()->getParam('billmate', false);
        if (!$billmate) {
            return $result;
        }

        $result = $this->jsonResultFactory->create();

        // Collect errors
        $messages = $this->messageManager->getMessages();
        $responseData = [
            'errors' => []
        ];

        $hasErrors = false;
        foreach ($messages->getItems() as $message) {
            if ($message instanceof Error) {
                $hasErrors = true;
                $responseData['errors'][] = $message->getText();
            }
        }

        if ($hasErrors) {
            $result->setJsonData(json_encode($responseData));
            return $result;
        }

        return $result->setJsonData(json_encode($this->constructSuccessResponse()));
    }

    abstract protected function constructSuccessResponse(): array;
}
