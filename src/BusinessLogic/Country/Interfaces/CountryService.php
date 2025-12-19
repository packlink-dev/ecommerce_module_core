<?php

namespace Packlink\BusinessLogic\Country\Interfaces;

use Packlink\BusinessLogic\Country\Models\Country;

interface CountryService
{
    /**
     * Returns a list of supported country DTOs.
     *
     * @param bool $associative Indicates whether the result should be an associative array.
     *
     * @return Country[]
     *
     */
    public function getSupportedCountries($associative = true);
}