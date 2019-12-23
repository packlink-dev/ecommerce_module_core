<?php

namespace Logeecom\Tests\BusinessLogic\Order;

use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderShipmentDetailsEntityTest
 *
 * @package Logeecom\Tests\BusinessLogic\Order
 */
class OrderShipmentDetailsEntityTest extends TestCase
{
    /**
     * Tests if all properties within Packlink order details entity are being properly set and returned.
     */
    public function testProperties()
    {
        $orderDetails = $this->getTestOrderDetails();
        $this->validateOrderDetails($orderDetails);
    }

    /**
     * Tests conversion of Packlink order shipment details entity object from array.
     */
    public function testFromArray()
    {
        $orderDetails = OrderShipmentDetails::fromArray(
            array(
                'orderId' => 5,
                'reference' => 'DE2019PRO0000309473',
                'dropOffId' => 23,
                'shipmentLabels' => array(
                    array(
                        'link' => 'test1.dev',
                        'printed' => true,
                        'createTime' => 1554192735,
                    ),
                    array(
                        'link' => 'test2.dev',
                        'printed' => false,
                        'createTime' => 1554192735,
                    ),
                ),
                'status' => ShipmentStatus::STATUS_PENDING,
                'lastStatusUpdateTime' => 1554192735,
                'carrierTrackingNumbers' => $this->getTestTrackingNumbers(),
                'carrierTrackingUrl' => 'https://www.ups.com/track?loc=it_IT&requester=WT/',
                'shippingCost' => 12.99,
                'taskId' => 312,
            )
        );

        $this->validateOrderDetails($orderDetails);
    }

    /**
     * Tests conversion of Packlink order shipment details entity object to array.
     */
    public function testToArray()
    {
        $orderDetails = $this->getTestOrderDetails();
        $orderDetailsArray = $orderDetails->toArray();

        self::assertEquals(5, $orderDetailsArray['orderId']);
        self::assertEquals('DE2019PRO0000309473', $orderDetailsArray['reference']);
        self::assertEquals(23, $orderDetailsArray['dropOffId']);

        $labels = $orderDetailsArray['shipmentLabels'];

        self::assertCount(2, $labels);
        self::assertThat($labels[0], self::arrayHasKey('link'));
        self::assertEquals('test1.dev', $labels[0]['link']);
        self::assertThat($labels[1], self::arrayHasKey('link'));
        self::assertEquals('test2.dev', $labels[1]['link']);
        self::assertEquals(ShipmentStatus::STATUS_PENDING, $orderDetailsArray['status']);
        self::assertEquals(1554192735, $orderDetailsArray['lastStatusUpdateTime']);
        self::assertEquals($this->getTestTrackingNumbers(), $orderDetailsArray['carrierTrackingNumbers']);
        self::assertEquals(
            'https://www.ups.com/track?loc=it_IT&requester=WT/',
            $orderDetailsArray['carrierTrackingUrl']
        );
        self::assertEquals(12.99, $orderDetailsArray['shippingCost']);
    }

    /**
     * Returns order shipment details entity with test data properties.
     *
     * @return OrderShipmentDetails
     */
    private function getTestOrderDetails()
    {
        $orderDetails = new OrderShipmentDetails();

        $orderDetails->setOrderId(5);
        $orderDetails->setReference('DE2019PRO0000309473');
        $orderDetails->setDropOffId(23);
        $orderDetails->setShipmentLabels(array(new ShipmentLabel('test1.dev'), new ShipmentLabel('test2.dev')));
        $orderDetails->setShippingStatus(ShipmentStatus::STATUS_PENDING, 1554192735);
        $orderDetails->setCarrierTrackingNumbers($this->getTestTrackingNumbers());
        $orderDetails->setCarrierTrackingUrl('https://www.ups.com/track?loc=it_IT&requester=WT/');
        $orderDetails->setShippingCost(12.99);

        return $orderDetails;
    }

    /**
     * Validates if values in provided order shipment details object match expected ones.
     *
     * @param OrderShipmentDetails $orderDetails Packlink order shipment details entity.
     */
    private function validateOrderDetails(OrderShipmentDetails $orderDetails)
    {
        self::assertEquals(5, $orderDetails->getOrderId());
        self::assertEquals('DE2019PRO0000309473', $orderDetails->getReference());
        self::assertEquals(23, $orderDetails->getDropOffId());

        $labels = $orderDetails->getShipmentLabels();

        self::assertCount(2, $labels);
        self::assertEquals('test1.dev', $labels[0]->getLink());
        self::assertEquals('test2.dev', $labels[1]->getLink());
        self::assertEquals(ShipmentStatus::STATUS_PENDING, $orderDetails->getShippingStatus());
        self::assertEquals(1554192735, $orderDetails->getLastStatusUpdateTime()->getTimestamp());
        self::assertEquals($this->getTestTrackingNumbers(), $orderDetails->getCarrierTrackingNumbers());
        self::assertEquals(
            'https://www.ups.com/track?loc=it_IT&requester=WT/',
            $orderDetails->getCarrierTrackingUrl()
        );
        self::assertEquals(12.99, $orderDetails->getShippingCost());
    }

    /**
     * Returns a set of test tracking numbers.
     *
     * @return array Order tracking numbers.
     */
    private function getTestTrackingNumbers()
    {
        return array(
            '1Z204E380338943508',
            '1ZXF38300382722839',
            '1ZW6897XYW00098770',
        );
    }
}
