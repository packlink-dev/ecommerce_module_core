<?php

namespace Logeecom\Tests\BusinessLogic\CashOnDelivery;

use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery as CashOnDeliveryDTO;

class TestCashOnDeliveryService implements CashOnDeliveryServiceInterface
{
    /**
     * Pre-set CashOnDelivery entity to return.
     *
     * @var CashOnDelivery|null
     */
    private $entity;

    /**
     * History of method calls for testing purposes.
     *
     * @var array
     */
    public $callHistory = array();

    /**
     * @var string $systemId
     */
    public $systemId;

    /**
     * @param string $systemId
     */
    public function setSystemId($systemId)
    {
        $this->systemId = $systemId;
    }

    /**
     * Sets the entity to be returned by getCashOnDeliveryConfig.
     *
     * @param CashOnDelivery|null $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        $this->callHistory['setEntity'][] = $entity;
    }

    public function getCashOnDeliveryConfig()
    {
        return $this->entity;
    }

    public function saveEmptyObject()
    {

        $entity = new CashOnDelivery();
        $entity->setSystemId($this->systemId);
        $entity->setEnabled(false);
        $entity->setActive(false);

        $this->entity = $entity;

        return $this->entity;
    }

    public function disable()
    {
        if ($this->entity === null) {
            return null;
        }

        $this->entity->setEnabled(false);

        return $this->entity;
    }

    public function enable()
    {
        if ($this->entity === null) {
            return null;
        }

        $this->entity->setEnabled(true);

        return $this->entity;
    }

    public function saveConfig(CashOnDeliveryDTO $dto)
    {
        $entity = CashOnDelivery::fromArray($dto->toArray());

        $this->entity = $entity;

        return 1;
    }
}