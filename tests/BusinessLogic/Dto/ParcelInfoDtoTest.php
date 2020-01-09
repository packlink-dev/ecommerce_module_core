<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;

/**
 * Class ParcelInfoDtoTest.
 *
 * @package BusinessLogic\Dto
 */
class ParcelInfoDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\DtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\DtoNotRegisteredException
     */
    public function testFromArray()
    {
        TestFrontDtoFactory::register('parcel', ParcelInfo::CLASS_NAME);
        $parcelsData = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/parcels.json'), true);

        /** @var ParcelInfo[] $parcels */
        $parcels = TestFrontDtoFactory::getFromBatch('parcel', $parcelsData);
        $this->assertCount(2, $parcels);
        $this->assertEquals(2, $parcels[0]->weight);
        $this->assertEquals(3, $parcels[0]->length);
        $this->assertEquals(6, $parcels[0]->height);
        $this->assertEquals(3, $parcels[0]->width);

        $this->assertEquals(5, $parcels[1]->weight);
        $this->assertEquals(6, $parcels[1]->length);
        $this->assertEquals(3, $parcels[1]->height);
        $this->assertEquals(2, $parcels[1]->width);
    }
}
