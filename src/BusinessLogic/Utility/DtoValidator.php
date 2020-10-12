<?php

namespace Packlink\BusinessLogic\Utility;

/**
 * Class DtoValidator
 *
 * @package Packlink\BusinessLogic\Utility
 */
class DtoValidator
{
    /**
     * Return whether the provided email address is in valid format.
     *
     * @param string $email
     *
     * @return bool
     */
    public static function isEmailValid($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Returns whether the provided phone number is in valid format.
     *
     * @param string $phone
     *
     * @return bool
     */
    public static function isPhoneValid($phone)
    {
        $regex = '/^[0-9|\/\-\s \+\.\(\)]+$/i';

        return !empty($phone) && preg_match($regex, $phone);
    }
}
