<?php

namespace Packlink\BusinessLogic\User\Interfaces;

interface UserAccountServiceInterface
{
    /**
     * Validates provided API key and initializes user's data.
     *
     * @param string $apiKey API key.
     *
     * @return bool TRUE if login went successfully; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\Brand\Exceptions\PlatformCountryNotSupportedByBrandException
     */
    public function login($apiKey);

    /**
     * Sets default parcel information.
     *
     * @param bool $force Force retrieval of parcel info from Packlink API.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function setDefaultParcel($force);

    /**
     * Sets warehouse information.
     *
     * @param bool $force Force retrieval of warehouse info from Packlink API.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function setWarehouseInfo($force);
}