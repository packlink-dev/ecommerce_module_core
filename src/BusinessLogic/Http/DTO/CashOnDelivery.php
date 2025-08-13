<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\Account;

class CashOnDelivery extends DataTransferObject
{
    /** @var bool */
    public $enabled = false;

    /** @var bool */
    public $active = false;

    /** @var Account */
    public $account;

    public function __construct()
    {
        $this->account = new Account();
    }
    public function toArray()
    {
        return array(
            'enabled' => $this->enabled,
            'active' => $this->active,
            'account' => $this->account->toArray(),
        );
    }
}