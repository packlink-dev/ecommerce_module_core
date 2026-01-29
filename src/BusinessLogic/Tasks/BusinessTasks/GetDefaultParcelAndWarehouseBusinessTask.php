<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class GetDefaultParcelAndWarehouseBusinessTask
 *
 * @package Packlink\BusinessLogic\Tasks\BusinessTasks
 */
class GetDefaultParcelAndWarehouseBusinessTask implements BusinessTask
{
    /**
     * Runs task logic.
     *
     * @return \Generator
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpBaseException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute(): \Generator
    {
        /** @var UserAccountService $userAccountService */
        $userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);

        $userAccountService->setDefaultParcel(true);
        yield 50;

        $userAccountService->setWarehouseInfo(true);
        yield 100;
    }

    /**
     * Serialize task to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array();
    }

    /**
     * Deserialize task from array.
     *
     * @param array $data
     *
     * @return BusinessTask
     */
    public static function fromArray(array $data): BusinessTask
    {
        return new static();
    }
}
