<?php

namespace Billmate\NwtBillmateCheckout\Test\Unit\Model\Api\Client\DTO\Response;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Response\PaymentInfo;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DataObject;

class PaymentInfoTest extends TestCase
{
    /**
     * Simulates a decoded 'data' section of a response from the GetPaymentInfo API call
     * @link https://billmate.github.io/api-docs/#getpaymentinfo
     *
     * @var array
     */
    private $mockData = [
        'PaymentData' => [
            'method' => '1',
            'paymentplanid' => '1',
            'currency' => 'SEK',
            'country' => 'SE',
            'language' => 'sv',
            'autoactivate' => '0',
            'orderid' => '1000001',
            'status' => 'Created',
            'paymentid_related' => '2',
            'url' => 'https://billmate.se'
        ],
        'PaymentInfo' => [
            'paymentdate' => '2022-01-01',
            'paymentterms' => '14',
            'yourreference' => 'purchaser x',
            'ourreference' => 'seller y',
            'projectname' => 'project z',
            'deliverymethod' => 'post',
            'deliveryterms' => 'FOB'
        ],
        'Card' => [
            'promptname' => '',
            '3dsecure' => '',
            'recurring' => '',
            'recurringnr' => '',
            'accepturl' => 'https://billmate.test/billmate/processing/saveOrder',
            'cancelurl' => 'https://billmate.test/billmate/processing/cancelOrder',
            'callbackurl' => 'https://billmate.test/billmate/processing/callback',
            'returnmethod' => 'POST'
        ],
        'Settlement' => [
            'number' => '1',
            'date' => '2022-01-01'
        ],
        'Customer' => [
            'nr' => '1',
            'pno' => '195501011018',
            'Billing' => [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'company' => 'Company',
                'street' => 'Street',
                'street2' => 'Street2',
                'zip' => '12345',
                'city' => 'Lund',
                'country' => 'SE',
                'phone' => '0712345678',
                'email' => 'test@developer.billmate.se'
            ],
            'Shipping' => [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'company' => 'Company',
                'street' => 'Shipping Street',
                'street2' => 'Shipping Street2',
                'zip' => '23456',
                'city' => 'ShippingCity',
                'country' => 'SE',
                'phone' => '0711345678',
                'email' => 'test@developer.billmate.se'
            ]
        ],
        'Articles' => [
            [
                'artnr' => 'testsku1',
                'title' => 'TestArticle',
                'quantity' => '2',
                'aprice' => '1234',
                'tax' => '617',
                'discount' => '0',
                'withouttax' => '2468',
                'taxrate' => '25',
            ],
            [
                'artnr' => 'testsku2',
                'title' => 'TestArticle2',
                'quantity' => '3.5',
                'aprice' => '56780',
                'tax' => '44714',
                'discount' => '10',
                'withouttax' => '178857',
                'taxrate' => '25'
            ]
        ],
        'Cart' => [
            'Handling' => [
                'withouttax' => '1000',
                'taxrate' => '25'
            ],
            'Shipping' => [
                'withouttax' => '3000',
                'taxrate' => '25'
            ],
            'Total' => [
                'rounding' => '44',
                'withouttax' => '185325',
                'tax' => '46331'
            ]
        ]
    ];

    /**
     * @var DataObjectFactory|MockObject
     */
    private $dataObjectFactory;

    public function setUp(): void
    {
        $this->dataObjectFactory = $this->createMock(DataObjectFactory::class);
    }

    public function testPopulateWithApiResponse()
    {

        $this->dataObjectFactory->method('create')->willReturnCallback(function () {
            return new DataObject();
        });

        $paymentInfo = new PaymentInfo(
            $this->dataObjectFactory
        );

        $paymentInfo->populateWithApiResponse($this->mockData);
        $this->assertEquals(
            $this->mockData['PaymentData']['orderid'],
            $paymentInfo->getPaymentData()->getOrderid()
        );
        $this->assertEquals(
            $this->mockData['PaymentData']['paymentplanid'],
            $paymentInfo->getPaymentData()->getPaymentplanid()
        );
        $this->assertEquals(
            $this->mockData['Articles'][0]['withouttax'],
            $paymentInfo->getArticles()[0]->getWithouttax()
        );
        $this->assertEquals(
            $this->mockData['Cart']['Total']['withouttax'],
            $paymentInfo->getCart()->getTotal()->getWithouttax()
        );
        $this->assertFalse(
            (bool)$paymentInfo->getPaymentData()->getAutoactivate()
        );
    }
}
