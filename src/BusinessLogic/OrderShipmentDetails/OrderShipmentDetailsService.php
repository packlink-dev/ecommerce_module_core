<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails;

use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class OrderShipmentDetailsService
 *
 * @package Packlink\BusinessLogic\OrderShipmentDetails
 */
class OrderShipmentDetailsService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Order shipment details repository.
     *
     * @var OrderShipmentDetailsRepository
     */
    protected $repository;

    /**
     * OrderShipmentDetailsService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->repository = new OrderShipmentDetailsRepository();
    }

    /**
     * Retrieves order shipment details.
     *
     * @param string | int $orderId Order id in an integration system.
     *
     * @return OrderShipmentDetails|null Instance for the specified order id, if found.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getDetailsByOrderId($orderId)
    {
        return $this->repository->selectByOrderId($orderId);
    }

    /**
     * Retrieves order shipment details.
     *
     * @param string $shipmentReference Shipment reference.
     *
     * @return OrderShipmentDetails|null Instance for the specified reference, if found.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getDetailsByReference($shipmentReference)
    {
        return $this->getDetailsByReferenceInternal($shipmentReference, false);
    }

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getIncompleteOrderReferences()
    {
        return $this->repository->getIncomplete();
    }

    /**
     * Retrieves list of order references where order is in one of the provided statuses.
     *
     * @param array $statuses List of order statuses.
     *
     * @return string[] Array of shipment references.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getOrderReferencesWithStatus(array $statuses)
    {
        return $this->repository->selectByStatus($statuses);
    }

    /**
     * Sets shipment reference number. Creates new object if it does not exist.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function setReference($orderId, $shipmentReference)
    {
        $orderDetails = $this->getDetailsByOrderId($orderId);

        if ($orderDetails === null) {
            $orderDetails = new OrderShipmentDetails();
            $orderDetails->setOrderId($orderId);
        }

        $orderDetails->setReference($shipmentReference);
        $orderDetails->setStatus(ShipmentStatus::STATUS_PENDING);

        $this->repository->persist($orderDetails);
    }

    /**
     * Sets shipment tracking URL and numbers.
     *
     * @param string $shipmentReference
     * @param string $trackingUrl
     * @param array $trackingNumbers
     */
    public function setTrackingInfo($shipmentReference, $trackingUrl, array $trackingNumbers)
    {
        /** @var OrderShipmentDetails $orderDetails */
        $orderDetails = $this->getDetailsByReferenceInternal($shipmentReference);

        $orderDetails->setCarrierTrackingUrl($trackingUrl);
        $orderDetails->setCarrierTrackingNumbers($trackingNumbers);

        $this->repository->persist($orderDetails);
    }

    /**
     * Sets order packlink shipping status.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $shippingStatus Packlink shipping status.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function setShippingStatus($shipmentReference, $shippingStatus)
    {
        /** @var OrderShipmentDetails $orderDetails */
        $orderDetails = $this->getDetailsByReferenceInternal($shipmentReference);
        if ($orderDetails === null) {
            throw new OrderShipmentDetailsNotFound(
                'Order details not found for reference: ' . $shipmentReference . '.'
            );
        }

        $orderDetails->setShippingStatus($shippingStatus);

        $this->repository->persist($orderDetails);
    }

    /**
     * Sets shipping price.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param float $price Shipment price.
     */
    public function setShippingPrice($shipmentReference, $price)
    {
        /** @var OrderShipmentDetails $orderDetails */
        $orderDetails = $this->getDetailsByReferenceInternal($shipmentReference);
        $orderDetails->setShippingCost($price);

        $this->repository->persist($orderDetails);
    }

    /**
     * Sets packlink shipment labels.
     *
     * @param string $shipmentReference Shipment reference.
     * @param string[] $labels Packlink shipment labels.
     */
    public function setLabelsByReference($shipmentReference, array $labels)
    {
        /** @var OrderShipmentDetails $orderDetails */
        $orderDetails = $this->getDetailsByReferenceInternal($shipmentReference);
        $orderDetails->setShipmentLabels(ShipmentLabel::fromArrayBatch($labels));

        $this->repository->persist($orderDetails);
    }

    /**
     * Marks shipment identified by provided reference as deleted on Packlink.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function markShipmentDeleted($shipmentReference)
    {
        $orderDetails = $this->getDetailsByReference($shipmentReference);
        if ($orderDetails !== null) {
            $orderDetails->setDeleted(true);
            $this->repository->persist($orderDetails);
        }
    }

    /**
     * Returns whether shipment identified by provided reference is deleted on Packlink or not.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if shipment has been deleted; otherwise returns FALSE.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function isShipmentDeleted($shipmentReference)
    {
        $orderDetails = $this->getDetailsByReference($shipmentReference);

        return $orderDetails === null || $orderDetails->isDeleted();
    }

    /**
     * Retrieves order shipment details.
     * Throws an exception if shipment details do not exist and throwing is requested.
     *
     * @param string $shipmentReference
     * @param bool $throwException Specifies whether to throw an exception if details are not found.
     *
     * @return \Packlink\BusinessLogic\Order\Models\OrderShipmentDetails|null
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    protected function getDetailsByReferenceInternal($shipmentReference, $throwException = true)
    {
        $details = $this->repository->selectByReference($shipmentReference);

        if ($details === null && $throwException) {
            throw new OrderShipmentDetailsNotFound(
                'Order details not found for reference "' . $shipmentReference . '".'
            );
        }

        return $details;
    }
}
