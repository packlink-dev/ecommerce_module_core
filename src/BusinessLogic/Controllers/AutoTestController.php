<?php

namespace Packlink\BusinessLogic\Controllers;

use Exception;
use Logeecom\Infrastructure\AutoTest\AutoTestLogger;
use Logeecom\Infrastructure\AutoTest\AutoTestService;
use Logeecom\Infrastructure\ServiceRegister;

/**
 * Class AutoTestController
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class AutoTestController
{
    /**
     * Auto test service.
     *
     * @var \Logeecom\Infrastructure\AutoTest\AutoTestService
     */
    protected $service;

    /**
     * AutoTestController constructor.
     */
    public function __construct()
    {
        $this->service = ServiceRegister::getService(AutoTestService::CLASS_NAME);
    }

    /**
     * Starts autotest.
     *
     * @return array
     */
    public function start()
    {
        try {
            $status = array('success' => true, 'itemId' => $this->service->startAutoTest());
        } catch (Exception $e) {
            $status = array('success' => false, 'error' => $e->getMessage());
        }

        return $status;
    }

    /**
     * Stops autotest mode.
     *
     * @param callable $loggerInitializer Method that will be used to re-register proper shop logger service.
     */
    public function stop(callable $loggerInitializer)
    {
        $this->service->stopAutoTestMode($loggerInitializer);
    }

    /**
     * Retrieves autotest status.
     *
     * @param int $id Auto test task id.
     *
     * @return array
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function checkStatus($id)
    {
        $status = $this->service->getAutoTestTaskStatus($id);

        return array(
            'finished' => $status->finished,
            'error' => $status->error,
            'logs' => AutoTestLogger::getInstance()->getLogsArray(),
        );
    }

    /**
     * Retrieves auto test logs.
     *
     * @return array
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getLogs()
    {
        return AutoTestLogger::getInstance()->getLogsArray();
    }
}