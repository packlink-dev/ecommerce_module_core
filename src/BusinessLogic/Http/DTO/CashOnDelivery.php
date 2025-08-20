<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
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

    /**
     * @param array $data
     *
     * @return static
     * @throws FrontDtoValidationException
     */
    public static function fromArray(array $data)
    {
        $instance = new static();

        if (isset($data['enabled'])) {
            $instance->enabled = (bool)$data['enabled'];
        }

        if (isset($data['active'])) {
            $instance->active = (bool)$data['active'];
        }

        if (isset($data['account']) && is_array($data['account'])) {
            $instance->account = Account::fromArray($data['account']);
        }

        return $instance;
    }
}