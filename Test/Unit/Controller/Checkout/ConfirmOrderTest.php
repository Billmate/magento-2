<?php

namespace Billmate\NwtBillmateCheckout\Test\Unit\Controller\Checkout;

use Billmate\NwtBillmateCheckout\Controller\Checkout\Confirmorder;
use Billmate\NwtBillmateCheckout\Controller\ControllerUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\OrderUtil;
use Billmate\NwtBillmateCheckout\Model\Utils\DataUtil;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfirmOrderTest extends TestCase
{
    /**
     * @var ControllerUtil|MockObject
     */
    private $controllerUtil;

    /**
     * @var OrderUtil|MockObject
     */
    private $orderUtil;

    /**
     * @var DataUtil|MockObject
     */
    private $dataUtil;

    /**
     * @var SubscriptionManager|MockObject
     */
    private $subscriptionManager;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * General setup for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->controllerUtil = $this->createMock(ControllerUtil::class);
        $this->orderUtil = $this->createMock(OrderUtil::class);
        $this->dataUtil = $this->createMock(DataUtil::class);
        $this->subscriptionManager = $this->createMock(SubscriptionManager::class);

        $requestParams = [
            'credentials' => '{"hash":"hash"}',
            'data' => '{"number":"number","status":"Created","orderid":"orderid","url":"url"}'
        ];
        $request = $this->createMock(HttpRequest::class);
        $request->method('getParams')->willReturn($requestParams);

        $credentialsObj = new DataObject(['hash' => 'hash']);
        $dataObj = new DataObject(['number' => 'number', 'status' => 'Created', 'orderid' => 'orderid', 'url' => 'url']);

        $requestObj = new DataObject();
        $requestObj->setData(['credentials' => $credentialsObj, 'data' => $dataObj]);

        $this->dataUtil->method('unserialize')
            ->willReturnOnConsecutiveCalls($credentialsObj, $dataObj)
        ;

        $this->dataUtil->method('createDataObject')->willReturn($requestObj);
        $this->controllerUtil->method('getRequest')->willReturn($request);

        $this->config = $this->createMock(Config::class);
        $this->dataUtil->method('getConfig')->willReturn($this->config);
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods([
                'getBillmateQuoteId',
                'getBillmateSubscribeNewsletter',
                'getBillmatePaymentNumber',
                'unsBillmatePaymentNumber'
            ])
            ->onlyMethods(['getQuote', 'clearQuote'])
            ->getMock()
        ;
        $this->controllerUtil->method('getCheckoutSession')->willReturn($this->checkoutSession);
    }

    /**
     * Tests that on success, no errors are displayed and redirect to success happens
     *
     * @return void
     */
    public function testSucessfulExecute()
    {
        $this->setupSuccessMock();
        $confirmOrder = new Confirmorder(
            $this->controllerUtil,
            $this->orderUtil,
            $this->dataUtil,
            $this->subscriptionManager
        );
        $this->dataUtil->expects($this->never())
            ->method('displayErrorMessage');

        $this->dataUtil->expects($this->never())
            ->method('displayExceptionMessage');

        $this->controllerUtil->expects($this->once())->method('redirect')->with('billmate/checkout/success');
        $confirmOrder->execute();
    }

    /**
     * Tests that on failure and in test mode, all error messages are displayed and redirect to cart happens 
     *
     * @return void
     */
    public function testUnsuccessfulExecuteTestmode()
    {
        $this->setupFailureMock();
        $this->config->method('getTestMode')->willReturn(true);
        $this->dataUtil->expects($this->exactly(3))
            ->method('displayErrorMessage')
            ->withConsecutive(
                ['Invalid hash in request from Billmate'],
                ['Order with this increment ID (orderid) already exists in Magento'],
                ['No quote ID found in the session']
            );
        $this->controllerUtil->expects($this->once())->method('redirect')->with('checkout/cart');
        $confirmOrder = new Confirmorder(
            $this->controllerUtil,
            $this->orderUtil,
            $this->dataUtil,
            $this->subscriptionManager
        );
        $confirmOrder->execute();
    }

    /**
     * Tests that on failure and in production mode, default error message is displayed and redirect to cart happens 
     *
     * @return void
     */
    public function testUnsuccessfulExecuteLivemode()
    {
        $this->setupFailureMock();
        $this->config->method('getTestMode')->willReturn(false);
        $this->config->method('getDefaultErrorMessage')->willReturn('default');
        $this->dataUtil->expects($this->exactly(1))
            ->method('displayErrorMessage')
            ->with('default')
        ;
        $this->controllerUtil->expects($this->once())->method('redirect')->with('checkout/cart');
        $confirmOrder = new Confirmorder(
            $this->controllerUtil,
            $this->orderUtil,
            $this->dataUtil,
            $this->subscriptionManager
        );
        $confirmOrder->execute();
    }

    /**
     * Setup mocks for a successful response
     *
     * @return void
     */
    private function setupSuccessMock()
    {
        $this->dataUtil->method('verifyHash')->willReturn(true);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(null);

        $quote = $this->createMock(Quote::class);
        $quote->method('getId')->willReturn(1);
        $payment = $this->createMock(Payment::class);
        $quote->method('getPayment')->willReturn($payment);
        $this->checkoutSession->method('getBillmateQuoteId')->willReturn(1);
        $this->checkoutSession->method('getBillmateSubscribeNewsletter')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->orderUtil->method('loadOrderByIncrementId')->willReturn($order);
    }

    /**
     * Setup mocks for failure
     *
     * @return void
     */
    private function setupFailureMock()
    {
        $this->dataUtil->method('verifyHash')->willReturn(false);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);

        $quote = $this->createMock(Quote::class);
        $quote->method('getId')->willReturn(null);
        $payment = $this->createMock(Payment::class);
        $quote->method('getPayment')->willReturn($payment);
        $this->checkoutSession->method('getBillmateQuoteId')->willReturn(null);
        $this->checkoutSession->method('getBillmateSubscribeNewsletter')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->orderUtil->method('loadOrderByIncrementId')->willReturn($order);
    }
}