<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Controller\Cart;

class CouponPostPlugin extends AbstractPlugin
{
    protected function constructSuccessResponse(): array
    {
        return ['success' => true];
    }
}
