<?php


namespace Logeecom\Tests\Brands\Packlink;


use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;

class BaseTestWithServices extends \Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices
{
    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    protected function before()
    {
        parent::before();

        TestServiceRegister::registerService(
            BrandConfigurationService::CLASS_NAME,
            function () {
                return new PacklinkConfigurationService();
            });
    }
}
