<?php

namespace Billmate\NwtBillmateCheckout\ViewModel\Adminhtml;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Billmate\NwtBillmateCheckout\Gateway\Http\Adapter\BillmateAdapter;

class Payment implements ArgumentInterface
{
    /**
     * @var BillmateAdapter
     */
    private $billmateAdapter;

    public function __construct(
        BillmateAdapter $billmateAdapter
    ) {
        $this->billmateAdapter = $billmateAdapter;
    }

    public function getPaymentinfo($number)
    {
        $paymentData = $this->billmateAdapter->getPaymentInfo($number);
        return $paymentData;
    }
}
