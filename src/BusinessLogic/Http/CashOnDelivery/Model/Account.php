<?php

namespace Packlink\BusinessLogic\Http\CashOnDelivery\Model;

use Packlink\BusinessLogic\DTO\FrontDto;

class Account extends FrontDto
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var array
     */
    protected static $fields = array(
        'accountHolderName',
        'iban',
        'cashOnDeliveryFee',
        'offlinePaymentMethod',
    );

    /** @var string */
    protected $accountHolderName = '';

    /** @var string */
    protected $iban = '';

    /** @var float */
    protected $cashOnDeliveryFee = 0.0;

    /** @var string */
    protected $offlinePaymentMethod = '';
}