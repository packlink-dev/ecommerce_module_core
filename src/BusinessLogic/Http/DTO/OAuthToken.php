<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

class OAuthToken extends DataTransferObject
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $tokenType;

    /**
     * @var int
     */
    private $expiresIn;

    /**
     * @var string
     */
    private $refreshToken;

    public function __construct(
        $accessToken,
        $tokenType,
        $expiresIn,
        $refreshToken
    ) {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Converts DTO to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
        );
    }
}