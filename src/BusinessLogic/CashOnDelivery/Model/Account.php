<?php

namespace Packlink\BusinessLogic\CashOnDelivery\Model;

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
    /**
     * @return string
     */
    public function getOfflinePaymentMethod()
    {
        return $this->offlinePaymentMethod;
    }

    /**
     * @return float
     */
    public function getCashOnDeliveryFee()
    {
        return $this->cashOnDeliveryFee;
    }

    /**
     * @return string
     */
    public function getAccountHolderName()
    {
        return $this->accountHolderName;
    }

    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }
}