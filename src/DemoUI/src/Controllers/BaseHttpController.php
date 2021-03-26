<?php

namespace Packlink\DemoUI\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class BaseHttpController.
 *
 * @package Packlink\DemoUI\Controllers
 */
class BaseHttpController
{
    /**
     * @var bool
     */
    protected $requiresAuthentication = true;
    /**
     * @var ConfigurationService
     */
    private $configService;

    public function isAuthenticated()
    {
        return !$this->requiresAuthentication || $this->getConfigService()->getAuthorizationToken();
    }

    /**
     * Gets the configuration service instance.
     *
     * @return ConfigurationService
     */
    protected function getConfigService()
    {
        if (!$this->configService) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Outputs the given array.
     *
     * @param array $data
     */
    protected function output(array $data)
    {
        echo json_encode($data);
    }

    /**
     * Outputs DTO entities as a JSON encoded array.
     *
     * @param array $data
     */
    protected function outputDtoEntities(array $data)
    {
        $this->output(
            array_map(
                function ($entity) {
                    return $entity->toArray();
                },
                $data
            )
        );
    }
}
