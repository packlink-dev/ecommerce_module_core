<?php

namespace Packlink\BusinessLogic\Location\Interfaces;

interface LocationService
{
    /**
     * Returns array of locations for this shipping method.
     *
     * @param int $shippingMethodId Shipping method identifier.
     * @param string $toCountry Country code to where package is shipped.
     * @param string $toPostCode Post code to where package is shipped.
     * @param array $packages Packages for which to find service.
     *
     * @return array Locations.
     */
    public function getLocations($shippingMethodId, $toCountry, $toPostCode, array $packages = array());

    /**
     * Performs search for locations.
     *
     * @param string $country Country code to search in.
     * @param string $query Query to search for.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Location\Exceptions\PlatformCountryNotSupportedException
     */
    public function searchLocations($country, $query);

}