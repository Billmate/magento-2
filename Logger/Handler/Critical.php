<?php

namespace Billmate\NwtBillmateCheckout\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Critical extends Base
{
    /**
     * @var string
     */
    protected $fileName = 'var/log/billmatecheckout/critical.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::CRITICAL;
}
