<?php

namespace Packlink\BusinessLogic\OAuth\Services\Interfaces;

use Packlink\BusinessLogic\Http\DTO\OAuthConnectData;
use Packlink\BusinessLogic\Http\DTO\OAuthToken;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;

interface OAuthServiceInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * @param OAuthConnectData $data
     */
    public function connect(OAuthConnectData $data);

    /**
     * @param $accessToken
     *
     * @return string
     */
    public function getApiKey($accessToken);

    /**
     * @param $authorizationCode
     *
     * @return OAuthToken
     */
    public function getToken($authorizationCode);

    /**
     * @param $refreshToken
     *
     * @return OAuthToken
     */
    public function refreshToken($refreshToken);

    /**
     * @param OAuthUrlData $data
     *
     * @return string
     */
    public function buildRedirectUrlAndSaveState(OAuthUrlData $data);

    /**
     * @param $state
     *
     * @return string
     */
    public function getTenantId($state);
}