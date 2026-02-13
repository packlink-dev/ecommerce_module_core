<?php

namespace Packlink\BusinessLogic\UpdateShippingServices;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

class UpdateShippingServiceTaskStatusService implements UpdateShippingServiceTaskStatusServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Saves shipping method.
     *
     * @param UpdateShippingServiceTaskStatus $shippingMethod Shipping method to delete.
     */
    public function save(UpdateShippingServiceTaskStatus $status)
    {
        $this->repository->save($status);
    }

    /**
     * Updates existing status.
     *
     * @param UpdateShippingServiceTaskStatus $status
     */
    public function update(UpdateShippingServiceTaskStatus $status)
    {
        $this->repository->update($status);
    }

    /**
     * Deletes status.
     *
     * @param UpdateShippingServiceTaskStatus $status
     */
    public function delete(UpdateShippingServiceTaskStatus $status)
    {
        $this->repository->delete($status);
    }

    /**
     * Returns latest status for given context.
     *
     * @param string $context
     *
     * @return \Logeecom\Infrastructure\ORM\Entity
     * @throws QueryFilterInvalidParamException
     */
    public function getLatestByContext($context)
    {
        $filter = new QueryFilter();
        $filter->where('context', '=', (string)$context);
        $filter->orderBy('createdAt', QueryFilter::ORDER_DESC);
        $filter->setLimit(1);

        return $this->repository->selectOne($filter);
    }

    /**
     * Creates or updates latest status entity for context.
     *
     * @param string $context
     * @param string $status
     * @param string|null $error
     * @param bool $finished
     *
     * @return UpdateShippingServiceTaskStatus
     */
    public function upsertStatus($context, $status, $error = null, $finished = false)
    {
        $now = time();

        /** @var UpdateShippingServiceTaskStatus|null $entity */
        $entity = $this->getLatestByContext($context);

        if (!$entity) {
            $entity = new UpdateShippingServiceTaskStatus();
            $entity->setContext((string)$context);
            $entity->setCreatedAt($now);
        }

        $entity->setStatus((string)$status);
        $entity->setUpdatedAt($now);
        $entity->setError($error);

        if ($finished) {
            $entity->setFinishedAt($now);
        } else {
            $entity->setFinishedAt(null);
        }

        if (!$entity->getId()) {
            $this->save($entity);
        } else {
            $this->update($entity);
        }

        return $entity;
    }

    /**
     * Returns latest status string for context.
     *
     * @param string $context
     *
     * @return string|null
     */
    public function getLatestStatus($context)
    {
        $entity = $this->getLatestByContext($context);

        return $entity ? $entity->getStatus() : null;
    }

}