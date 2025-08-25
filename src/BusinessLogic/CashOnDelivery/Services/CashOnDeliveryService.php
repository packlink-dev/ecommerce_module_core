<?php

namespace Packlink\BusinessLogic\CashOnDelivery\Services;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Model\Account;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;

class CashOnDeliveryService implements CashOnDeliveryServiceInterface
{
    /**
     * @var RepositoryInterface $repository
     */
    protected $repository;

    /** @var Configuration $config */
    protected $configurationService;

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function __construct()
    {
        $this->repository = RepositoryRegistry::getRepository(CashOnDelivery::CLASS_NAME);

        /** @var Configuration $config */
        $this->configurationService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Calculate COD surcharge fee if it is not set in the configuration than use from api.
     *
     * @param float $orderTotal Total order amount
     * @param float $percentage Percentage fee
     * @param float $minFee Minimum fee
     *
     * @return float COD surcharge
     * @throws QueryFilterInvalidParamException
     */
    public function calculateFee($orderTotal, $percentage, $minFee)
    {
        $calculated = round($orderTotal * ($percentage / 100), 2);

        if ($calculated < $minFee) {
            return $minFee;
        }

        return $calculated;
    }

    /**
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getCashOnDeliveryConfig()
    {
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('systemId', '=', $this->configurationService->getCurrentSystemId());

        /**@var CashOnDelivery|null $entity*/
        $entity = $this->repository->selectOne($filter);

        return $entity;
    }

    /**
     * @param CashOnDeliveryDTO $dto
     * @return int
     * @throws QueryFilterInvalidParamException
     */
    public function saveConfig(CashOnDeliveryDTO $dto)
    {
        $entity = CashOnDelivery::fromArray($dto->toArray());
        $entity->setSystemId($this->configurationService->getCurrentSystemId());


        /** @var CashOnDelivery|null $existing */
        $existing = $this->getCashOnDeliveryConfig();

        if ($existing) {
            $entity->setId($existing->getId());
            return $this->repository->update($entity);
        }

        return $this->repository->save($entity);
    }

    /**
     * Create a new CacheOnDelivery object and save
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function disable()
    {
        $entity = $this->getCashOnDeliveryConfig();
        if (!$entity) {
            return null;
        }

        $entity->setEnabled(false);

        $this->repository->update($entity);

        return $this->getCashOnDeliveryConfig();
    }

    /**
     * Disable COD and return freshly loaded entity.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function enable()
    {
        $entity = $this->getCashOnDeliveryConfig();
        if (!$entity) {
            return null;
        }

        $entity->setEnabled(true);

        $this->repository->update($entity);

        return $this->getCashOnDeliveryConfig();
    }
}