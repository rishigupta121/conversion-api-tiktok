<?php
namespace RG\Tiktok\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class RGHandler
 * @
 */
class RGHandler extends Base
{
    protected $loggerType = Logger::DEBUG;
    protected $fileName = '/var/log/Tiktok.log';
}
