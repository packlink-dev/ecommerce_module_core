<?php

namespace Logeecom\Tests\BusinessLogic\Tasks;

use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\TaskExecution\Task;
use Logeecom\Tests\BusinessLogic\BaseSyncTest;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Tasks\GetDefaultParcelAndWarehouseTask;
use Packlink\BusinessLogic\User\UserAccountService;

/**
 * Class GetDefaultParcelAndWarehouseTaskTest
 * @package Logeecom\Tests\BusinessLogic\Tasks
 */
class GetDefaultParcelAndWarehouseTaskTest extends BaseSyncTest
{
    /**
     * @var \Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient
     */
    public $httpClient;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        TestServiceRegister::registerService(
            UserAccountService::CLASS_NAME,
            function () {
                return UserAccountService::getInstance();
            }
        );

        TestServiceRegister::registerService(Proxy::CLASS_NAME, function () use ($me) {
            /** @var Configuration $config */
            $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

            return new Proxy($config, $me->httpClient);
        });
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        UserAccountService::resetInstance();
        parent::tearDown();
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function testExecute()
    {
        $this->httpClient->setMockResponses($this->getMockResponses());
        $this->syncTask->execute();

        $this->assertCount(3, $this->httpClient->getHistory());

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
            new HttpResponse(
                200, array(), file_get_contents(__DIR__ . '/../Common/ApiResponses/user.json')
            ),
        );
    }
}
