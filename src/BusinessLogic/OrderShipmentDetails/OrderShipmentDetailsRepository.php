<?php

namespace Packlink\BusinessLogic\OrderShipmentDetails;

use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class OrderShipmentDetailsRepository.
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
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
    }

    /**
     * Retrieves order shipment details for provided order ID.
     *
     * @param string | int $orderId Order id in an integration system.
     *
     * @return OrderShipmentDetails|null Instance for the specified order id, if found.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function selectByOrderId($orderId)
    {
        $query = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $query->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderShipmentDetails | null $entity */
        $entity = $this->repository->selectOne($query);

        return $entity;
    }

    /**
     * Retrieves order shipment details for provided shipment reference.
     *
     * @param string $reference Shipment reference.
     *
     * @return OrderShipmentDetails|null Instance for the specified reference, if found.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function selectByReference($reference)
    {
        $query = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
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
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function selectByStatus(array $statuses)
    {
        $filter = new QueryFilter();

        foreach ($statuses as $status) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $filter->orWhere('status', Operators::EQUALS, $status);
        }

        return $this->getShipmentsReferences($filter);
    }

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getIncomplete()
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('status', Operators::NOT_EQUALS, ShipmentStatus::STATUS_DELIVERED);

        return $this->getShipmentsReferences($filter);
    }

    /**
     * Persists the entity to the database.
     *
     * @param OrderShipmentDetails $details The entity.
     *
     * @return bool The operation success.
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
