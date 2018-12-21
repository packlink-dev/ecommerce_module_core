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
     * Runs task logic.
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
