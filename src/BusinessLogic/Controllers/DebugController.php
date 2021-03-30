<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

/**
 * Class DebugController
 *
 * @package Packlink\Middleware\Http\Controllers
 */
class DebugController
{
    const SYSTEM_INFO_FILE_NAME = 'packlink-debug-data.zip';
    const USER_INFO_FILE_NAME = 'user-settings.json';
    const QUEUE_INFO_FILE_NAME = 'queue.json';
    const SERVICE_INFO_FILE_NAME = 'services.json';
    /**
     * @var ConfigurationService
     */
    private $configService;

    public function __construct()
    {
        $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Returns debug mode status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->configService->isDebugModeEnabled();
    }

    /**
     * Sets debug mode status.
     *
     * @param bool $status New debug status.
     */
    public function setStatus($status)
    {
        $this->configService->setDebugModeEnabled($status);
    }

    /**
     * Returns system info file.
     *
     * @throws \Exception
     */
    public function getSystemInfo()
    {
        if (!defined('JSON_PRETTY_PRINT')) {
            define('JSON_PRETTY_PRINT', 128);
        }

        if (!defined('JSON_UNESCAPED_SLASHES')) {
            define('JSON_UNESCAPED_SLASHES', 64);
        }

        $file = tempnam(sys_get_temp_dir(), 'packlink_system_info');

        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::CREATE);

        $zip->addFromString(static::USER_INFO_FILE_NAME, $this->getUserSettings());
        $zip->addFromString(static::QUEUE_INFO_FILE_NAME, $this->getQueue());
        $zip->addFromString(static::SERVICE_INFO_FILE_NAME, $this->getServicesInfo());

        $this->getIntegrationInfo($zip);

        $zip->close();

        return $file;
    }

    /**
     * Returns integration specific information.
     * An extension point for integrations to add more data.
     *
     * @param \ZipArchive $zip
     */
    protected function getIntegrationInfo(\ZipArchive $zip)
    {
    }

    /**
     * Returns parcel and warehouse information.
     *
     * @return string
     */
    protected function getUserSettings()
    {
        $result = array();
        /** @noinspection NullPointerExceptionInspection */
        $result['User'] = $this->configService->getUserInfo()->toArray();
        $result['User']['API key'] = $this->configService->getAuthorizationToken();
        $result['Parcel'] = $this->configService->getDefaultParcel() ?: array();
        $result['Warehouse'] = $this->configService->getDefaultWarehouse() ?: array();
        $result['Order Status Mappings'] = $this->configService->getOrderStatusMappings() ?: array();

        return $this->jsonEncode($result);
    }

    /**
     * Returns service info.
     *
     * @return string
     */
    protected function getServicesInfo()
    {
        $result = array();

        try {
            $repository = RepositoryRegistry::getRepository(RepositoryInterface::CLASS_NAME);
            $result = $repository->select();
        } catch (RepositoryNotRegisteredException $e) {
        }

        return $this->formatJsonOutput($result);
    }

    /**
     * Returns current queue for current tenant.
     *
     * @return string
     */
    protected function getQueue()
    {
        $result = array();

        try {
            $repository = RepositoryRegistry::getQueueItemRepository();

            $query = new QueryFilter();
            $query->where('context', Operators::EQUALS, $this->configService->getContext());

            $result = $repository->select($query);
        } catch (RepositoryNotRegisteredException $e) {
        } catch (QueryFilterInvalidParamException $e) {
        } catch (RepositoryClassException $e) {
        }

        return $this->formatJsonOutput($result);
    }

    /**
     * Encodes the given data.
     *
     * @param $data
     *
     * @return string
     */
    protected function jsonEncode($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Formats json output.
     *
     * @param \Logeecom\Infrastructure\ORM\Entity[] $items Entities.
     *
     * @return string
     */
    protected function formatJsonOutput(array $items)
    {
        $response = array();
        foreach ($items as $item) {
            $response[] = $item->toArray();
        }

        return $this->jsonEncode($response);
    }
}
