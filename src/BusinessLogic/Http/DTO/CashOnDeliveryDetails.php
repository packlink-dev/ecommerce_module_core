<?php

namespace Packlink\BusinessLogic\Http\DTO;

class CashOnDeliveryDetails
{
    /** @var float */
    private $amount;

    /** @var string */
    private $accountHolder;

    /** @var string */
    private $iban;

    /**
     * @param float $amount
     * @param string $accountHolder
     * @param string $iban
     */
    public function __construct($amount, $accountHolder, $iban)
    {
        $this->amount = $amount;
        $this->accountHolder = $accountHolder;
        $this->iban = $iban;
    }

    public function toArray()
    {
        return array(
                'amount' => $this->amount,
                'account_holder' => $this->accountHolder,
                'iban' => $this->iban,
        );
    }
}