<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;

/**
 * Class OrderShipmentDetails.
 *
 * @package Packlink\BusinessLogic\Order\Models
 */
class OrderShipmentDetails extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'orderId',
        'reference',
        'dropOffId',
        'shipmentLabels',
        'status',
        'lastStatusUpdateTime',
        'carrierTrackingNumbers',
        'carrierTrackingUrl',
        'shippingCost',
        'shipmentUrl',
        'deleted',
        'currency',
    );
    /**
     * Shop order ID.
     *
     * @var string
     */
    private $orderId;
    /**
     * Shipment reference.
     *
     * @var string
     */
    private $reference;
    /**
     * Drop off location ID.
     *
     * @var string
     */
    private $dropOffId;
    /**
     * Order shipment labels.
     *
     * @var ShipmentLabel[]
     */
    private $shipmentLabels;
    /**
     * Tracking status.
     *
     * @var string
     */
    private $status;
    /**
     * Date and time of last status update.
     *
     * @var \DateTime
     */
    private $lastStatusUpdateTime;
    /**
     * Array of carrier tracking numbers.
     *
     * @var array
     */
    private $carrierTrackingNumbers;
    /**
     * Carrier tracking URL.
     *
     * @var string
     */
    private $carrierTrackingUrl;
    /**
     * Packlink shipping price.
     *
     * @var float
     */
    private $shippingCost;
    /**
     * Currency ISO code.
     *
     * @var string
     */
    private $currency;
    /**
     * Shipment URL.
     *
     * @var string
     */
    private $shipmentUrl;
    /**
     * If order has been deleted on the system.
     *
     * @var bool
     */
    private $deleted = false;

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();

        $map->addStringIndex('orderId');
        $map->addStringIndex('reference');
        $map->addStringIndex('status');

        return new EntityConfiguration($map, 'OrderShipmentDetails');
    }

    /**
     * Sets raw array data to this entity instance properties.
     *
     * @param array $data Raw array data with keys for class fields. @see self::$fields for field names.
     *
     * @throws \Exception
     */
    public function inflate(array $data)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        foreach ($this->fields as $fieldName) {
            if ($fieldName === 'shipmentLabels' && !empty($data['shipmentLabels'])) {
                $this->shipmentLabels = ShipmentLabel::fromBatch($data['shipmentLabels']);
            } elseif ($fieldName === 'lastStatusUpdateTime' && !empty($data['lastStatusUpdateTime'])) {
                $this->lastStatusUpdateTime = $timeProvider->getDateTime($data['lastStatusUpdateTime']);
            } elseif ($fieldName === 'currency') {
                $this->currency = static::getDataValue($data, $fieldName, 'EUR');
            } else {
                $this->$fieldName = static::getArrayValue($data, $fieldName);
            }
        }
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $data = array();

        foreach ($this->fields as $fieldName) {
            if ($fieldName === 'shipmentLabels' && $this->shipmentLabels !== null) {
                foreach ($this->shipmentLabels as $shipmentLabel) {
                    $data['shipmentLabels'][] = $shipmentLabel->toArray();
                }
            } elseif ($fieldName === 'lastStatusUpdateTime') {
                $data[$fieldName] = $this->lastStatusUpdateTime ? $this->lastStatusUpdateTime->getTimestamp() : null;
            } elseif ($fieldName === 'currency') {
                $data[$fieldName] = $this->currency ?: 'EUR';
            } else {
                $data[$fieldName] = $this->$fieldName;
            }
        }

        return $data;
    }

    /**
     * Returns order ID.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Sets order ID.
     *
     * @param string $orderId ID of the order.
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Returns shipment reference.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Sets order shipping reference.
     *
     * @param string $reference Shipping reference.
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * Returns order shipment labels.
     *
     * @return ShipmentLabel[]
     */
    public function getShipmentLabels()
    {
        return $this->shipmentLabels ?: array();
    }

    /**
     * Sets order shipment labels from array of links to PDF.
     *
     * @param ShipmentLabel[] Array of shipment labels.
     */
    public function setShipmentLabels(array $labels)
    {
        $this->shipmentLabels = $labels;
    }

    /**
     * Returns order status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets order status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns order shipping status.
     *
     * @return string
     */
    public function getShippingStatus()
    {
        return $this->status ?: '';
    }

    /**
     * Sets order shipping status.
     *
     * @param string $status Order shipping status.
     * @param int $updateTime Last shipping status update timestamp.
     */
    public function setShippingStatus($status, $updateTime = null)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $this->status = $status;

        if ($updateTime === null) {
            $this->lastStatusUpdateTime = $timeProvider->getCurrentLocalTime();
        } else {
            $this->lastStatusUpdateTime = $timeProvider->getDateTime($updateTime);
        }
    }

    /**
     * Returns array of carrier tracking numbers.
     *
     * @return array
     */
    public function getCarrierTrackingNumbers()
    {
        return $this->carrierTrackingNumbers ?: array();
    }

    /**
     * Sets carrier tracking numbers.
     *
     * @param array $carrierTrackingNumbers Array of carrier tracking numbers.
     */
    public function setCarrierTrackingNumbers($carrierTrackingNumbers)
    {
        $this->carrierTrackingNumbers = $carrierTrackingNumbers;
    }

    /**
     * Returns last status update time.
     *
     * @return \DateTime
     */
    public function getLastStatusUpdateTime()
    {
        return $this->lastStatusUpdateTime;
    }

    /**
     * Returns Packlink shipping price.
     *
     * @return float
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * Sets Packlink shipping price.
     *
     * @param float $shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;
    }

    /**
     * Returns drop-off identifier.
     *
     * @return string
     */
    public function getDropOffId()
    {
        return $this->dropOffId;
    }

    /**
     * Sets drop-off identifier.
     *
     * @param string $dropOffId
     */
    public function setDropOffId($dropOffId)
    {
        $this->dropOffId = $dropOffId;
    }

    /**
     * Returns carrier tracking URL.
     *
     * @return string
     */
    public function getCarrierTrackingUrl()
    {
        return $this->carrierTrackingUrl;
    }

    /**
     * Sets carrier tracking URL.
     *
     * @param string $carrierTrackingUrl
     */
    public function setCarrierTrackingUrl($carrierTrackingUrl)
    {
        $this->carrierTrackingUrl = $carrierTrackingUrl;
    }

    /**
     * Returns whether this order has been deleted on the system.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Sets deleted flag on the order.
     *
     * @param bool $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * Gets Shipment Url.
     *
     * @return string Shipment Url.
     */
    public function getShipmentUrl()
    {
        return $this->shipmentUrl;
    }

    /**
     * Sets ShipmentUrl.
     *
     * @param string $shipmentUrl ShipmentUrl.
     */
    public function setShipmentUrl($shipmentUrl)
    {
        $this->shipmentUrl = $shipmentUrl;
    }

    /**
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}
