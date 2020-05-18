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
        $regex = '/^(\ |\+|\/|\.\|-|\(|\)|\d)+$/m';
        $phoneError = !preg_match($regex, $phone);

        $digits = '/\d/m';
        $match = preg_match_all($digits, $phone, $matches);
        $phoneError |= $match === false || $match < 3;

        return !$phoneError;
    }
}
