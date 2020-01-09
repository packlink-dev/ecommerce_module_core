<?php

namespace Packlink\BusinessLogic\ShipmentDraft;

use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;

/**
 * Class OrderSendDraftTaskRepository.
 *
 * @package Packlink\BusinessLogic\ShipmentDraft
 */
class OrderSendDraftTaskRepository
{
    /**
     * OrderSendDraftTaskMap repository.
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * OrderSendDraftTaskRepository constructor.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(OrderSendDraftTaskMap::getClassName());
    }

    /**
     * Retrieves Order - SendDraftTask map instance.
     *
     * @param string | int $orderId Order id in an integration system.
     *
     * @return OrderSendDraftTaskMap|null An entity for the specified order id, if found.
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function selectByOrderId($orderId)
    {
        $query = new QueryFilter();
        $query->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderSendDraftTaskMap | null $entity */
        $entity = $this->repository->selectOne($query);

        return $entity;
    }

    /**
     * Retrieves Order - SendDraftTask map instance.
     *
     * @param string $executionId Task execution ID.
     *
     * @return OrderSendDraftTaskMap|null Instance for the specified reference, if found.
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function selectByExecutionId($executionId)
    {
        $query = new QueryFilter();
        $query->where('executionId', Operators::EQUALS, $executionId);

        /** @var OrderSendDraftTaskMap | null $entity */
        $entity = $this->repository->selectOne($query);

        return $entity;
    }

    /**
     * Persists the entity to the database.
     *
     * @param OrderSendDraftTaskMap $map The entity.
     *
     * @return bool The operation success.
     */
    public function persist(OrderSendDraftTaskMap $map)
    {
        if ($map->getId() === null) {
            return $this->repository->save($map) !== 0;
        }

        return $this->repository->update($map);
    }
}
