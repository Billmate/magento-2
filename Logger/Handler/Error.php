<?php

namespace Billmate\NwtBillmateCheckout\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Error extends Base
{
    /**
     * @var string
     */
    protected $fileName = 'var/log/billmatecheckout/error.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;
}