<?php

namespace Billmate\NwtBillmateCheckout\Exception;

use Magento\Framework\Exception\LocalizedException;

class AdapterException extends LocalizedException
{
    private $errorCodes = [
        '9011' => 'Invalid credentials',
        '9013' => 'Authentication is failed'
    ];

    public function check()
    {
        return $this->getCode();
    }
}