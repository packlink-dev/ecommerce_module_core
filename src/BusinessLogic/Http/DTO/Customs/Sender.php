<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Sender
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class Sender extends DataTransferObject
{
    /**
     * @var string
     */
    public $userType;
    /**
     * @var string
     */
    public $fullName;
    /**
     * @var string
     */
    public $taxId;
    /**
     * @var string
     */
    public $companyName;
    /**
     * @var string
     */
    public $vatNumber;
    /**
     * @var string
     */
    public $eoriNumber;
    /**
     * @var string
     */
    public $address;
    /**
     * @var string
     */
    public $postalCode;
    /**
     * @var string
     */
    public $city;
    /**
     * @var string
     */
    public $country;
    /**
     * @var string
     */
    public $phoneNumber;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->userType = static::getDataValue($data, 'user_type');
        $result->fullName = static::getDataValue($data, 'full_name');
        $result->taxId = static::getDataValue($data, 'tax_id');
        $result->companyName = static::getDataValue($data, 'company_name');
        $result->vatNumber = static::getDataValue($data, 'vat_number');
        $result->eoriNumber = static::getDataValue($data, 'eori_number');
        $result->address = static::getDataValue($data, 'address');
        $result->postalCode = static::getDataValue($data, 'postal_code');
        $result->city = static::getDataValue($data, 'city');
        $result->country = static::getDataValue($data, 'country');
        $result->phoneNumber = static::getDataValue($data, 'phone_number');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'user_type' => $this->userType,
            'full_name' => $this->fullName,
            'tax_id' => $this->taxId,
            'company_name' => $this->companyName,
            'vat_number' => $this->vatNumber,
            'eori_number' => $this->eoriNumber,
            'address' => $this->address,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
            'phone_number' => $this->phoneNumber,
        );
    }
}
