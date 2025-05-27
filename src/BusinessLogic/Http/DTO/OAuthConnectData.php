<?php

namespace Packlink\BusinessLogic\Http\DTO;

class OAuthConnectData
{
    /** @var string */
    private $authorizationCode;

    /*** @var string */
    private $state;

    public function __construct($authorizationCode, $state)
    {
        $this->authorizationCode = $authorizationCode;
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getAuthorizationCode()
    {
        return $this->authorizationCode;
    }
}