<?php

namespace Packlink\BusinessLogic\OAuth\Proxy\Interfaces;

use Packlink\BusinessLogic\Http\DTO\OAuthToken;

interface OAuthProxyInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * Exchanges the authorization code for access and refresh tokens.
     *
     * @param $authorizationCode
     *
     * @return OAuthToken
     */
    public function getAuthToken($authorizationCode);

    /**
     * Uses the refresh token to get a new access token.
     *
     * @param string $refreshToken Refresh token obtained from previous token exchange
     *
     * @return OAuthToken
     */
    public function refreshAuthToken($refreshToken);
}