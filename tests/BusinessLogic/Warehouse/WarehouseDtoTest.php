<?php

namespace Logeecom\Tests\BusinessLogic\Warehouse;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Dto\BaseDtoTest;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class WarehouseDtoTest.
 *
 * @package BusinessLogic\Dto
 */
class WarehouseDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testFromArray()
    {
        TestFrontDtoFactory::register(Warehouse::CLASS_KEY, Warehouse::CLASS_NAME);
        $data = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/warehouses.json'), true);

        /** @var \Packlink\BusinessLogic\Warehouse\Warehouse[] $warehouses */
        $warehouses = TestFrontDtoFactory::getFromBatch(Warehouse::CLASS_KEY, $data);

        $this->assertCount(2, $warehouses);

        $this->assertEquals('El Piquillo 2', $warehouses[0]->city);
        $this->assertEquals('MyLastname2', $warehouses[0]->surname);
        $this->assertEquals('MyName2', $warehouses[0]->name);
        $this->assertEquals('1234567', $warehouses[0]->phone);
        $this->assertEquals('ES', $warehouses[0]->country);
        $this->assertEquals('MyCompanyName2', $warehouses[0]->company);
        $this->assertEquals('MyCompanyName2', $warehouses[0]->company);
        $this->assertEquals(true, $warehouses[0]->default);
        $this->assertEquals('example2@email.com', $warehouses[0]->email);
        $this->assertEquals('MyWarehouse2', $warehouses[0]->alias);
        $this->assertEquals('28041', $warehouses[0]->postalCode);
        $this->assertEquals('MyAddress2', $warehouses[0]->address);
        $this->assertEquals('222459d5e4b0ed5488fe91544', $warehouses[0]->id);

        $this->assertEquals('5be459d5e4b0ed5488fe9159', $warehouses[1]->id);
    }

    public function testToArray()
    {
        $warehouse = new TestWarehouse();

        $data = $warehouse->toArray();
        $this->assertArrayHasKey('default_selection', $data);
        $this->assertArrayHasKey('postal_code', $data);
    }
}