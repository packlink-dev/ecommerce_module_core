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


    /** @var float|null */
    protected $cashOnDeliveryFee;

    /** @var string */
    protected $offlinePaymentMethod = '';

    /**
     * @param string $accountHolderName
     */
    public function setAccountHolderName(string $accountHolderName)
    {
        $this->accountHolderName = $accountHolderName;
    }

    /**
     * @param string $iban
     */
    public function setIban(string $iban)
    {
        $this->iban = $iban;
    }

    /**
     * @param float|null $cashOnDeliveryFee
     */
    public function setCashOnDeliveryFee($cashOnDeliveryFee)
    {
        $this->cashOnDeliveryFee = $cashOnDeliveryFee;
    }

    /**
     * @param string $offlinePaymentMethod
     */
    public function setOfflinePaymentMethod(string $offlinePaymentMethod)
    {
        $this->offlinePaymentMethod = $offlinePaymentMethod;
    }

    /**
     * @return string
     */
    public function getOfflinePaymentMethod(): string
    {
        return $this->offlinePaymentMethod;
    }

    /**
     * @return float|null
     */
    public function getCashOnDeliveryFee()
    {
        return $this->cashOnDeliveryFee;
    }

    /**
     * @return string
     */
    public function getAccountHolderName(): string
    {
        return $this->accountHolderName;
    }

    /**
     * @return string
     */
    public function getIban(): string
    {
        return $this->iban;
    }
}