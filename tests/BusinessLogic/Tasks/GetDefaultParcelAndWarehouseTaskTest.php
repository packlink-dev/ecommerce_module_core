<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Logeecom\Infrastructure\TaskExecution\TaskAdapter;
use Packlink\BusinessLogic\Tasks\BusinessTasks\GetDefaultParcelAndWarehouseBusinessTask;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class GetDefaultParcelAndWarehouseTaskTest
 * @package Logeecom\Tests\BusinessLogic\Tasks
 */
class GetDefaultParcelAndWarehouseTaskTest extends BaseSyncTest
{
    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        parent::before();

        TestServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () {
                $scheduler = \Logeecom\Infrastructure\ServiceRegister::getService(
                    \Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface::class
                );
                $orchestrator = \Logeecom\Infrastructure\ServiceRegister::getService(
                    \Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface::class
                );

                return new UserAccountService($orchestrator, $scheduler);
            }
        );
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testExecute()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();

        $this->assertCount(2, $this->httpClient->getHistory());

        $parcelInfo = $this->shopConfig->getDefaultParcel();
        $this->assertNotNull($parcelInfo);

        $warehouse = $this->shopConfig->getDefaultWarehouse();
        $this->assertNotNull($warehouse);
        $this->assertEquals('222459d5e4b0ed5488fe91544', $warehouse->id);
    }

    /**
     * Creates new instance of task that is being tested.
     *
     * @return \Logeecom\Infrastructure\TaskExecution\Task
     */
    protected function createSyncTaskInstance()
    {
        $businessTask = new GetDefaultParcelAndWarehouseBusinessTask();

        return new TaskAdapter($businessTask);
    }

    /**
     * Returns responses for testing parcel and warehouse initialization.
     *
     * @return HttpResponse[] Array of Http responses.
     */
    private function getMockResponses()
    {
        return array(
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/parcels.json')
            ),
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json')
            ),
        );
    }
}
