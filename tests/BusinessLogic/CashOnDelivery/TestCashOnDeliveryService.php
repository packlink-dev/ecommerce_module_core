<?php

namespace Logeecom\Tests\BusinessLogic\CashOnDelivery;

use Packlink\BusinessLogic\Http\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\Http\CashOnDelivery\Model\CashOnDelivery;

class TestCashOnDeliveryService implements CashOnDeliveryServiceInterface
{
    /**
     * Pre-set CashOnDelivery entity to return.
     *
     * @var CashOnDelivery|null
     */
    private $entity = null;

    /**
     * History of method calls for testing purposes.
     *
     * @var array
     */
    public $callHistory = array();

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

    public function getCashOnDeliveryConfig($systemId)
    {
        $this->callHistory['getCashOnDeliveryConfig'][] = $systemId;

        return $this->entity;
    }

    public function saveEmptyObject($systemId)
    {
        $this->callHistory['saveEmptyObject'][] = $systemId;

        $entity = new CashOnDelivery();
        $entity->setSystemId($systemId);
        $entity->setEnabled(false);
        $entity->setActive(false);

        $this->entity = $entity;

        return $this->entity;
    }

    public function disable($systemId)
    {
        $this->callHistory['disable'][] = $systemId;

        if ($this->entity === null) {
            return null;
        }

        $this->entity->setEnabled(false);

        return $this->entity;
    }

    public function enable($systemId)
    {
        $this->callHistory['enable'][] = $systemId;

        if ($this->entity === null) {
            return null;
        }

        $this->entity->setEnabled(true);

        return $this->entity;
    }
}