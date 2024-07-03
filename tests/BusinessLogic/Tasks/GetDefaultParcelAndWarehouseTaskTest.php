<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Tasks\GetDefaultParcelAndWarehouseTask;
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
                return UserAccountService::getInstance();
            }
        );
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        UserAccountService::resetInstance();
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
     * @return Task
     */
    protected function createSyncTaskInstance()
    {
        return new GetDefaultParcelAndWarehouseTask();
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
