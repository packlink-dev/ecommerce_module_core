<?php

namespace Logeecom\Tests\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\Http\DTO\BFF\BffPostsaleResponse;
use Packlink\BusinessLogic\Http\DTO\BFF\BffSessionResponse;
use Packlink\BusinessLogic\Http\DTO\BFF\BffTrackingResponse;
use PHPUnit\Framework\TestCase;

/**
 * Class BffDtoTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Http\DTO
 */
class BffDtoTest extends TestCase
{
    /**
     * Tests that BffSessionResponse maps top-level keys.
     */
    public function testSessionFromArray()
    {
        $dto = BffSessionResponse::fromArray(
            array(
                'sessionId' => 'sess-1',
                'tenantName' => 'packlink',
                'platform' => 'PRO',
                'platformCountry' => 'ES',
            )
        );

        self::assertSame('sess-1', $dto->sessionId);
        self::assertSame('packlink', $dto->tenantName);
        self::assertSame('PRO', $dto->platform);
        self::assertSame('ES', $dto->platformCountry);
    }

    /**
     * Tests that BffSessionResponse defaults to empty string for missing keys.
     */
    public function testSessionFromEmptyArray()
    {
        $dto = BffSessionResponse::fromArray(array());

        self::assertSame('', $dto->sessionId);
        self::assertSame('', $dto->tenantName);
        self::assertSame('', $dto->platform);
        self::assertSame('', $dto->platformCountry);
    }

    /**
     * Tests that BffPostsaleResponse extracts the nested shipmentData fields.
     */
    public function testPostsaleFromArrayNested()
    {
        $dto = BffPostsaleResponse::fromArray(
            array(
                'shipmentData' => array(
                    'shipmentDetails' => array(
                        'publicTrackingUrl' => 'https://pro.packlink.com/tracking/ABC123XYZ',
                        'orderReference' => 'ORD-987654',
                        'isDropOff' => true,
                    ),
                    'service' => array(
                        'carrierIcon' => 'https://cdn.packlink.com/carriers/dhl.png',
                        'name' => 'DHL Express',
                    ),
                    'shipmentStatus' => array(
                        'label' => 'In transit',
                    ),
                ),
            )
        );

        self::assertSame('https://pro.packlink.com/tracking/ABC123XYZ', $dto->publicTrackingUrl);
        self::assertSame('ORD-987654', $dto->orderReference);
        self::assertTrue($dto->isDropOff);
        self::assertSame('https://cdn.packlink.com/carriers/dhl.png', $dto->carrierIcon);
        self::assertSame('DHL Express', $dto->serviceName);
        self::assertSame('In transit', $dto->shipmentStatusLabel);
    }

    /**
     * Tests that BffPostsaleResponse falls back to defaults when nested structures are absent.
     */
    public function testPostsaleFromEmptyArray()
    {
        $dto = BffPostsaleResponse::fromArray(array());

        self::assertSame('', $dto->publicTrackingUrl);
        self::assertSame('', $dto->orderReference);
        self::assertFalse($dto->isDropOff);
        self::assertSame('', $dto->carrierIcon);
        self::assertSame('', $dto->serviceName);
        self::assertSame('', $dto->shipmentStatusLabel);
    }

    /**
     * Tests that BffTrackingResponse extracts the first parcel fields.
     */
    public function testTrackingFromArrayNested()
    {
        $dto = BffTrackingResponse::fromArray(
            array(
                'parcels' => array(
                    array(
                        'estimatedDeliveryDate' => '2026-06-15',
                        'currentStatus' => array(
                            'label' => array(
                                'key' => 'IN_TRANSIT',
                            ),
                        ),
                    ),
                ),
            )
        );

        self::assertSame('2026-06-15', $dto->estimatedDeliveryDate);
        self::assertSame('IN_TRANSIT', $dto->status);
    }

    /**
     * Tests that BffTrackingResponse falls back to defaults when there are no parcels.
     */
    public function testTrackingFromEmptyArray()
    {
        $dto = BffTrackingResponse::fromArray(array());

        self::assertSame('', $dto->estimatedDeliveryDate);
        self::assertSame('', $dto->status);
    }
}
