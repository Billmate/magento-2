<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Checkout\Controller\Cart as CartController;
use Magento\Framework\Message\Error;
use Magento\Checkout\Model\Session as CheckoutSession;
use Billmate\NwtBillmateCheckout\ViewModel\CartHtml;

abstract class AbstractPlugin
{

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var PageFactory
     */
    protected $pageResultFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartHtml
     */
    protected $cartHtml;

    public function __construct(
        JsonFactory $jsonResultFactory,
        MessageManagerInterface $messageManager,
        CheckoutSession $checkoutSession,
        CartHtml $cartHtml
    ) {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->cartHtml = $cartHtml;
    }

    /**
     * Return a json result if it was an ajax request from billmate checkout
     *
     * @param CartController $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(CartController $subject, ResultInterface $result)
    {
        $request = $subject->getRequest();
        $billmate = $request->getParam('billmate', false);
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
