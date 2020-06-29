<?php

namespace Packlink\BusinessLogic\Registration;

class RegistrationInfo
{
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $phone;
    /**
     * @var string
     */
    private $source;

    /**
     * RegistrationInfo constructor.
     *
     * @param string $email
     * @param string $phone
     * @param string $source
     */
    public function __construct($email, $phone, $source)
    {
        $this->email = $email !== null ? $email : '';
        $this->phone = $phone !== null ? $phone : '';
        $this->source = $source !== null ? $source : '';
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}
