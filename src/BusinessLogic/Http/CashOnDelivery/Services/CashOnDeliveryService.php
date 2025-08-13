<?php

namespace Packlink\BusinessLogic\Http\CashOnDelivery\Services;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;


class CashOnDeliveryService implements CashOnDeliveryServiceInterface
{
    /**
     * @var RepositoryInterface $repository
     */
    protected $repository;


    /**
     * @throws RepositoryNotRegisteredException
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(CashOnDelivery::CLASS_NAME);
    }

    /**
     * @param $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCashOnDeliveryConfig($systemId)
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('systemId', '=', $systemId);

        /**@var CashOnDelivery|null $entity*/
        $entity = $this->repository->selectOne($filter);

        return $entity;
    }

    /**
     * Create a new CacheOnDelivery object and save
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function saveEmptyObject($systemId)
    {
        $entity = $this->createEmptyCashOnDelivery($systemId);

        $this->repository->save($entity);

        return $this->getCashOnDeliveryConfig($systemId);
    }

    /**
     * Create a new CacheOnDelivery object and save
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function disable($systemId)
    {
        $entity = $this->getCashOnDeliveryConfig($systemId);
        if (!$entity) {
            return null;
        }

        $entity->setEnabled(false);

        $this->repository->update($entity);

        return $this->getCashOnDeliveryConfig($systemId);
    }

    /**
     * Disable COD and return freshly loaded entity.
     *
     * @param string $systemId
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function enable($systemId)
    {
        $entity = $this->getCashOnDeliveryConfig($systemId);
        if (!$entity) {
            return null;
        }

        $entity->setEnabled(true);

        $this->repository->update($entity);

        return $this->getCashOnDeliveryConfig($systemId);
    }


    /**
     * Create an empty CacheOnDelivery object
     *
     * @param string $systemId
     *
     * @return CashOnDelivery
     */
    private function createEmptyCashOnDelivery($systemId)
    {
        $cashOnDelivery = new CashOnDelivery();
        $cashOnDelivery->setSystemId($systemId);
        $cashOnDelivery->setEnabled(false);
        $cashOnDelivery->setActive(false);


        $account = new Account();
        $cashOnDelivery->setAccount($account);

        return $cashOnDelivery;
    }
}