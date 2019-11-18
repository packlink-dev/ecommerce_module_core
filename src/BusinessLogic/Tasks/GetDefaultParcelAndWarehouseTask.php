<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class GetDefaultParcelAndWarehouseTask
 * @package Packlink\BusinessLogic\Tasks
 */
class GetDefaultParcelAndWarehouseTask extends Task
{
    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static();
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array();
    }

    /**
     * Runs task logic.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     */
    public function execute()
    {
        /** @var UserAccountService $userAccountService */
        $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);

        $userAccountService->setDefaultParcel(true);
        $this->reportProgress(50);

        $userAccountService->setWarehouseInfo(true);
        $this->reportProgress(100);
    }
}
