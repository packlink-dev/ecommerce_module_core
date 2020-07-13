<?php


namespace Logeecom\Tests\Infrastructure\Common\TestComponents;


use Packlink\BusinessLogic\Registration\RegistrationInfo;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;

class TestRegistrationInfoService implements RegistrationInfoService
{

    public function getRegistrationInfoData()
    {
        return new RegistrationInfo('test@test.com', '1111111111111', '00000000000');
    }
}