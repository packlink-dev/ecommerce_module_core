<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class GetDefaultParcelAndWarehouseBusinessTask
 *
 * @package Packlink\BusinessLogic\Tasks\BusinessTasks
 */
class GetDefaultParcelAndWarehouseBusinessTask implements BusinessTask
{
    /**
     * Optional execution config override.
     *
     * @var TaskExecutionConfig|null
     */
    private $executionConfig;

    public function __construct(TaskExecutionConfig $executionConfig = null)
    {
        $this->executionConfig = $executionConfig;
    }

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
        $data = [];

        if ($this->executionConfig !== null) {
            $data['execution_config'] = $this->executionConfig->toArray();
        }

        return $data;
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
        $executionConfig = null;

        if (!empty($data['execution_config']) && is_array($data['execution_config'])) {
            $executionConfig = TaskExecutionConfig::fromArray($data['execution_config']);
        }

        return new static($executionConfig);
    }

    /**
     * @return TaskExecutionConfig|null
     */
    public function getExecutionConfig()
    {
        return $this->executionConfig;
    }
}
