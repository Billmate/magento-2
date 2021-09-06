<?php

namespace Billmate\NwtBillmateCheckout\Controller;

use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

class ControllerUtil
{
    private HttpRequest $request;

    private ForwardFactory $forwardFactory;

    private JsonFactory $jsonFactory;

    private RedirectFactory $redirectFactory;

    private PageFactory $pageFactory;

    private FormKeyValidator $formKeyValidator;

    private Session $checkoutSession;

    public function __construct(
        HttpRequest $request,
        ForwardFactory $forwardFactory,
        JsonFactory $jsonFactory,
        RedirectFactory $redirectFactory,
        PageFactory $pageFactory,
        FormKeyValidator $formKeyValidator,
        Session $checkoutSession
    ) {
        $this->request = $request;
        $this->forwardFactory = $forwardFactory;
        $this->jsonFactory = $jsonFactory;
        $this->redirectFactory = $redirectFactory;
        $this->pageFactory = $pageFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->request->isAjax();
    }

    /**
     * Creates a forward to 'noroute' (404)
     *
     * @return Forward
     */
    public function forwardNoRoute(): Forward
    {
        return $this->forwardFactory->create()->forward('noroute');
    }

    /**
     * Creates a redirect to provided path
     *
     * @param string $path
     * @return Redirect
     */
    public function redirect(string $path): Redirect
    {
        return $this->redirectFactory->create()->setPath($path);
    }

    /**
     * Creates a json result with provided data
     *
     * @param array $data
     * @return Json
     */
    public function jsonResult(array $data = []): Json
    {
        return $this->jsonFactory->create()->setData($data);
    }

    /**
     * Creates a page result
     *
     * @return Page
     */
    public function pageResult(): Page
    {
        return $this->pageFactory->create();
    }

    /**
     * Get request instance
     *
     * @return HttpRequest
     */
    public function getRequest(): HttpRequest
    {
        return $this->request;
    }

    /**
     * Form key validation
     *
     * @return bool
     */
    public function validateFormKey(): bool
    {
        return $this->formKeyValidator->validate($this->request);
    }

    /**
     * Wrapper for Magento\Checkout\Model\Session::getQuote()
     *
     * @return Quote
     */
    public function getQuote(): Quote
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get checkout session
     *
     * @return Session
     */
    public function getCheckoutSession(): Session
    {
        return $this->checkoutSession;
    }
}
