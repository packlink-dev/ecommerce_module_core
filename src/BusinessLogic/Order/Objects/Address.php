<?php

namespace Packlink\BusinessLogic\Order\Objects;

/**
 * Class Address
 * @package Packlink\BusinessLogic\Order\Objects
 */
class Address
{
    /**
     * Name of sender/receiver.
     *
     * @var string
     */
    private $name;
    /**
     * Surname of sender/receiver.
     *
     * @var string
     */
    private $surname;
    /**
     * Company of sender/receiver.
     *
     * @var string
     */
    private $company;
    /**
     * First line of the sender/receiver street address.
     *
     * @var string
     */
    private $street1;
    /**
     * Second line of the sender/receiver street address.
     *
     * @var string
     */
    private $street2;
    /**
     * The zip code (or postal code).
     *
     * @var string
     */
    private $zipCode;
    /**
     * Address city.
     *
     * @var string
     */
    private $city;
    /**
     * Address country ISO2 code.
     *
     * @var string
     */
    private $country;
    /**
     * The sender's/receiver's phone number.
     *
     * @var string
     */
    private $phone;
    /**
     * The sender's email address.
     *
     * @var string
     */
    private $email;

    /**
     * Returns name of sender/receiver.
     *
     * @return string Name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name of sender/receiver.
     *
     * @param string $name Name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns surname of sender/receiver.
     *
     * @return string Surname.
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Sets surname of sender/receiver.
     *
     * @param string $surname Surname.
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
    }

    /**
     * Returns company name of sender/receiver.
     *
     * @return string Company name.
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Sets company name of sender/receiver.
     *
     * @param string $company Company name.
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Returns first line of the sender/receiver street address.
     *
     * @return string Street address.
     */
    public function getStreet1()
    {
        return $this->street1;
    }

    /**
     * Sets first line of the sender/receiver street address.
     *
     * @param string $street1 Street address.
     */
    public function setStreet1($street1)
    {
        $this->street1 = $street1;
    }

    /**
     * Returns second line of the sender/receiver street address.
     *
     * @return string Street address.
     */
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * Sets second line of the sender/receiver street address.
     *
     * @param string $street2 Street address.
     */
    public function setStreet2($street2)
    {
        $this->street2 = $street2;
    }

    /**
     * Returns zip code (or postal code).
     *
     * @return string ZIP/Postal code.
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Sets zip code (or postal code).
     *
     * @param string $zipCode ZIP/Postal code.
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * Returns Address city.
     *
     * @return string City.
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets Address city.
     *
     * @param string $city City.
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Returns address country ISO2 code.
     *
     * @return string Country.
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets address country ISO2 code.
     *
     * @param string $country Country.
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns sender's/receiver's phone number.
     *
     * @return string Phone number.
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Sets sender's/receiver's phone number.
     *
     * @param string $phone Phone number.
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Returns sender's email address.
     *
     * @return string E-mail address.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets sender's email address.
     *
     * @param string $email E-mail address.
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
