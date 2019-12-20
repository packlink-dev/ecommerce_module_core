<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails;

use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class OrderShipmentDetailsRepository
 *
 * @package Packlink\BusinessLogic\OrderShipmentDetails
 */
class OrderShipmentDetailsRepository
{
    /**
     * Order shipment details repository.
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * OrderShipmentDetailsRepository constructor.
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
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
    public function selectByOrderId($orderId)
    {
        $query = new QueryFilter();
        $query->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderShipmentDetails | null $entity */
        $entity = $this->repository->selectOne($query);

        return $entity;
    }

    /**
     * Retrieves order shipment details by shipment reference.
     *
     * @param string $reference Shipment reference.
     *
     * @return OrderShipmentDetails|null Instance for the specified reference, if found.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function selectByReference($reference)
    {
        $query = new QueryFilter();
        $query->where('reference', Operators::EQUALS, $reference);

        /** @var OrderShipmentDetails | null $entity */
        $entity = $this->repository->selectOne($query);

        return $entity;
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
    public function selectByStatus(array $statuses)
    {
        $filter = new QueryFilter();

        foreach ($statuses as $status) {
            $filter->orWhere('status', Operators::EQUALS, $status);
        }

        return $this->getShipmentsReferences($filter);
    }

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getIncomplete()
    {
        $filter = new QueryFilter();
        $filter->where('status', Operators::NOT_EQUALS, ShipmentStatus::STATUS_DELIVERED);

        return $this->getShipmentsReferences($filter);
    }

    /**
     * Sets order packlink shipping status to an order by shipment reference.
     *
     * @param \Packlink\BusinessLogic\Order\Models\OrderShipmentDetails $details
     *
     * @return bool
     */
    public function persist(OrderShipmentDetails $details)
    {
        if ($details->getId() === null) {
            return $this->repository->save($details) !== 0;
        }

        return $this->repository->update($details);
    }

    /**
     * Retrieves list of shipment references for provided filter.
     *
     * @param \Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter $filter
     *
     * @return string[] Array of shipment references.
     */
    protected function getShipmentsReferences(QueryFilter $filter)
    {
        $orders = $this->repository->select($filter);

        $result = array();
        /** @var OrderShipmentDetails $order */
        foreach ($orders as $order) {
            if ($order->getReference() !== null) {
                $result[] = $order->getReference();
            }
        }

        return $result;
    }
}
