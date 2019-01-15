<?php

namespace Logeecom\Tests\Common\TestComponents\Logger;

use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;

class TestShopLogger implements ShopLoggerAdapter
{
    /**
     * @var LogData
     */
    public $data;
    /**
     * @var LogData[]
     */
    public $loggedMessages = array();

    public function logMessage(LogData $data)
    {
        $this->data = $data;
        $this->loggedMessages[] = $data;
    }

    public function isMessageContainedInLog($message)
    {
        foreach ($this->loggedMessages as $loggedMessage) {
            if (mb_strpos($loggedMessage->getMessage(), $message) !== false) {
                return true;
            }
        }

        return false;
    }
}