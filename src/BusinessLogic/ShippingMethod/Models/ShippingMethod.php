<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * This class represents shipping service from Packlink with specific data for integration.
 *
 * @package Packlink\BusinessLogic\ShippingMethod\Models
 */
class ShippingMethod extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Indicates that Packlink pricing policy is used.
     */
    const PRICING_POLICY_PACKLINK = 1;
    /**
     * Indicates that percent from Packlink price pricing policy is used.
     */
    const PRICING_POLICY_PERCENT = 2;
    /**
     * Indicates that fixed price pricing policy is used.
     */
    const PRICING_POLICY_FIXED = 3;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'serviceId',
        'serviceName',
        'carrierName',
        'title',
        'enabled',
        'activated',
        'logoUrl',
        'displayLogo',
        'departureDropOff',
        'destinationDropOff',
        'expressDelivery',
        'deliveryTime',
        'national',
        'pricingPolicy',
    );

    /**
     * Packlink service id.
     *
     * @var int
     */
    protected $serviceId;
    /**
     * Packlink service name.
     *
     * @var string
     */
    protected $serviceName;
    /**
     * Carrier name.
     *
     * @var string
     */
    protected $carrierName;
    /**
     * Method title.
     *
     * @var string
     */
    protected $title;
    /**
     * Indicates whether method is enabled.
     *
     * @var bool
     */
    protected $enabled = true;
    /**
     * Indicates whether method is activated.
     *
     * @var bool
     */
    protected $activated = false;
    /**
     * Public URL to the Packlink service logo.
     *
     * @var string
     */
    protected $logoUrl;
    /**
     * Indicates whether logo of the method should be displayed to the end customer.
     *
     * @var bool
     */
    protected $displayLogo = true;
    /**
     * Indicates whether service requires departure drop-off.
     *
     * @var bool
     */
    protected $departureDropOff;
    /**
     * Indicates whether service requires destination drop-off.
     *
     * @var bool
     */
    protected $destinationDropOff;
    /**
     * Indicates whether service supports express delivery.
     *
     * @var bool
     */
    protected $expressDelivery;
    /**
     * Estimated delivery time, e.g. 3 DAYS.
     *
     * @var string
     */
    protected $deliveryTime;
    /**
     * Indicates whether service is national or international.
     *
     * @var bool
     */
    protected $national = false;
    /**
     * Pricing policy used. Defaults to @see self::PRICING_POLICY_PACKLINK.
     *
     * @var int
     */
    protected $pricingPolicy = self::PRICING_POLICY_PACKLINK;
    /**
     * Array of fixed price policy data.
     *
     * @var FixedPricePolicy[]
     */
    protected $fixedPricePolicy;
    /**
     * Percent price policy data.
     *
     * @var PercentPricePolicy
     */
    protected $percentPricePolicy;
    /**
     * All costs for this shipping method.
     *
     * @var ShippingMethodCost[]
     */
    protected $shippingCosts = array();

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        if (!$this->getPricingPolicy()) {
            $this->pricingPolicy = static::PRICING_POLICY_PACKLINK;
        }

        if (!empty($data['fixedPricePolicy'])) {
            $policies = array();
            foreach ($data['fixedPricePolicy'] as $fixedPricePolicy) {
                $policies[] = FixedPricePolicy::fromArray($fixedPricePolicy);
            }

            $this->setFixedPricePolicy($policies);
        }

        if (!empty($data['percentPricePolicy'])) {
            $this->setPercentPricePolicy(PercentPricePolicy::fromArray($data['percentPricePolicy']));
        }

        if (!empty($data['shippingCosts'])) {
            foreach ($data['shippingCosts'] as $shippingCost) {
                $this->shippingCosts[] = ShippingMethodCost::fromArray($shippingCost);
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
        $data = parent::toArray();

        if ($this->fixedPricePolicy) {
            $policy = $this->fixedPricePolicy;
            foreach ($policy as $fixedPricePolicy) {
                $data['fixedPricePolicy'][] = $fixedPricePolicy->toArray();
            }
        }

        if ($this->percentPricePolicy) {
            $data['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->shippingCosts) {
            foreach ($this->shippingCosts as $shippingCost) {
                $data['shippingCosts'][] = $shippingCost->toArray();
            }
        }

        return $data;
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();

        $indexMap->addIntegerIndex('serviceId')
            ->addBooleanIndex('activated')
            ->addBooleanIndex('enabled')
            ->addBooleanIndex('departureDropOff')
            ->addBooleanIndex('destinationDropOff')
            ->addBooleanIndex('national')
            ->addBooleanIndex('expressDelivery');

        return new EntityConfiguration($indexMap, 'ShippingService');
    }

    /**
     * Gets Service Id.
     *
     * @return int Service Id.
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Sets service id.
     *
     * @param int $serviceId Service id.
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * Gets Service name.
     *
     * @return string Service name.
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Sets service name.
     *
     * @param string $serviceName Service name.
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    /**
     * Gets Carrier name.
     *
     * @return string Carrier name.
     */
    public function getCarrierName()
    {
        return $this->carrierName;
    }

    /**
     * Sets Carrier name.
     *
     * @param string Carrier name.
     */
    public function setCarrierName($carrierName)
    {
        $this->carrierName = $carrierName;
    }

    /**
     * Gets Title.
     *
     * @return string Title.
     */
    public function getTitle()
    {
        if (!$this->title) {
            return $this->getCarrierName() . ' ' . $this->getDeliveryTime() . ' '
                . ($this->isDestinationDropOff() ? 'drop-off' : 'home') . ' delivery';
        }

        return $this->title;
    }

    /**
     * Sets title.
     *
     * @param string $title Title of shipping method.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Indicates whether this shipping method is enabled for user.
     *
     * @return bool TRUE if enabled; otherwise, FALSE.
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets whether this shipping method is enabled for user.
     *
     * @param bool $enabled TRUE if enabled; otherwise, FALSE.
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Indicates whether this shipping method is activated in system.
     *
     * @return bool TRUE if activated; otherwise, FALSE.
     */
    public function isActivated()
    {
        return $this->activated;
    }

    /**
     * Sets whether this shipping method is activated in system.
     *
     * @param bool $activated TRUE if activated; otherwise, FALSE.
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;
    }

    /**
     * Gets logo URL.
     *
     * @return string Logo URL.
     */
    public function getLogoUrl()
    {
        return $this->logoUrl;
    }

    /**
     * Sets logo URL.
     *
     * @param string $logoUrl Logo URL.
     */
    public function setLogoUrl($logoUrl)
    {
        $this->logoUrl = $logoUrl;
    }

    /**
     * Indicates whether logo should be displayed for shipping method.
     *
     * @return bool TRUE if logo should be displayed; otherwise, FALSE.
     */
    public function isDisplayLogo()
    {
        return $this->displayLogo;
    }

    /**
     * Sets whether logo should be displayed for shipping method.
     *
     * @param bool $displayLogo TRUE if logo should be displayed; otherwise, FALSE.
     */
    public function setDisplayLogo($displayLogo)
    {
        $this->displayLogo = $displayLogo;
    }

    /**
     * Indicates whether service method supports only departure drop-off and not pick-up option.
     *
     * @return bool TRUE if only drop-off is supported; otherwise, FALSE.
     */
    public function isDepartureDropOff()
    {
        return $this->departureDropOff;
    }

    /**
     * Sets whether service method supports only departure drop-off and not pick-up option.
     *
     * @param bool $departureDropOff TRUE if only drop-off is supported; otherwise, FALSE.
     */
    public function setDepartureDropOff($departureDropOff)
    {
        $this->departureDropOff = $departureDropOff;
    }

    /**
     * Indicates whether service method supports only destination drop-off and not pick-up option.
     *
     * @return bool TRUE if only drop-off is supported; otherwise, FALSE.
     */
    public function isDestinationDropOff()
    {
        return $this->destinationDropOff;
    }

    /**
     * Sets whether service method supports only destination drop-off and not pick-up option.
     *
     * @param bool $destinationDropOff TRUE if only drop-off is supported; otherwise, FALSE.
     */
    public function setDestinationDropOff($destinationDropOff)
    {
        $this->destinationDropOff = $destinationDropOff;
    }

    /**
     * Indicates whether service supports express delivery
     *
     * @return bool TRUE if express delivery is supported; otherwise, FALSE.
     */
    public function isExpressDelivery()
    {
        return $this->expressDelivery;
    }

    /**
     * Sets whether service supports express delivery.
     *
     * @param bool $expressDelivery TRUE if express delivery is supported; otherwise, FALSE.
     */
    public function setExpressDelivery($expressDelivery)
    {
        $this->expressDelivery = $expressDelivery;
    }

    /**
     * Gets estimated delivery time, e.g. 3 DAYS.
     *
     * @return string Delivery time.
     */
    public function getDeliveryTime()
    {
        return $this->deliveryTime;
    }

    /**
     * Sets estimated delivery time, e.g. 3 DAYS.
     *
     * @param string $deliveryTime Delivery time.
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;
    }

    /**
     * Indicates whether service method supports only national shipment.
     *
     * @return bool TRUE if only national is supported; otherwise, FALSE.
     */
    public function isNational()
    {
        return $this->national;
    }

    /**
     * Sets whether service method supports only national shipment.
     *
     * @param bool $national TRUE if only national is supported; otherwise, FALSE.
     */
    public function setNational($national)
    {
        $this->national = $national;
    }

    /**
     * Gets shipping method costs.
     *
     * @return ShippingMethodCost[] Shipping method costs.
     */
    public function getShippingCosts()
    {
        return $this->shippingCosts ?: array();
    }

    /**
     * Sets shipping method costs.
     *
     * @param ShippingMethodCost[] $shippingCosts Shipping method costs.
     */
    public function setShippingCosts($shippingCosts)
    {
        $this->shippingCosts = $shippingCosts;
    }

    /**
     * Gets pricing policy. Value is one of the @see self::PRICING_POLICY_FIXED, @see self::PRICING_POLICY_PERCENT
     * of @see self::PRICING_POLICY_PACKLINK.
     *
     * @return int Pricing policy code.
     */
    public function getPricingPolicy()
    {
        return $this->pricingPolicy;
    }

    /**
     * Sets data for fixed price policy.
     *
     * @param FixedPricePolicy[] $fixedPricePolicy
     *
     * @throws \InvalidArgumentException
     */
    public function setFixedPricePolicy($fixedPricePolicy)
    {
        $this->percentPricePolicy = null;
        usort(
            $fixedPricePolicy,
            function (FixedPricePolicy $first, FixedPricePolicy $second) {
                return $first->from > $second->from;
            }
        );

        if (!$this->validateFixedPricePolicy($fixedPricePolicy)) {
            throw new \InvalidArgumentException('Fixed price policies are not valid. Check range and amounts.');
        }

        $this->fixedPricePolicy = $fixedPricePolicy;
        $this->pricingPolicy = self::PRICING_POLICY_FIXED;
    }

    /**
     * Gets Fixed price policy data.
     *
     * @return FixedPricePolicy[] FixedPricePolicy array.
     */
    public function getFixedPricePolicy()
    {
        return $this->fixedPricePolicy;
    }

    /**
     * Sets Percent pricing policy.
     *
     * @param PercentPricePolicy $percentPricePolicy Percent price policy data.
     */
    public function setPercentPricePolicy($percentPricePolicy)
    {
        if (!$this->validatePercentPricePolicy($percentPricePolicy)) {
            throw new \InvalidArgumentException('Percent price policy is not valid. Check range and amounts.');
        }

        $this->percentPricePolicy = $percentPricePolicy;
        $this->fixedPricePolicy = null;
        $this->pricingPolicy = self::PRICING_POLICY_PERCENT;
    }

    /**
     * Gets Percent pricing policy data.
     *
     * @return PercentPricePolicy PercentPricePolicy data.
     */
    public function getPercentPricePolicy()
    {
        return $this->percentPricePolicy;
    }

    /**
     * Sets default Packlink pricing policy.
     */
    public function setPacklinkPricePolicy()
    {
        $this->percentPricePolicy = null;
        $this->fixedPricePolicy = null;
        $this->pricingPolicy = self::PRICING_POLICY_PACKLINK;
    }

    /**
     * Validates whether fixed price policies are correct.
     * Rules for each policy:
     *   1. 'from' must be equal to 'to' of a previous policy, for first it must be 0
     *   2. 'to' must be greater than 'from'
     *   3. 'amount' must be a positive number
     *
     * @param FixedPricePolicy[] $fixedPricePolicies Policies array to validate.
     *
     * @return bool TRUE if all policies and their order are valid.
     */
    protected function validateFixedPricePolicy($fixedPricePolicies)
    {
        if (count($fixedPricePolicies) > 0) {
            $count = count($fixedPricePolicies);
            $previous = $fixedPricePolicies[0];
            if (!$this->isPolicyValid($previous, 0)) {
                return false;
            }

            for ($i = 1; $i < $count; $i++) {
                if (!$this->isPolicyValid($fixedPricePolicies[$i], $previous->to)) {
                    return false;
                }

                $previous = $fixedPricePolicies[$i];
            }
        }

        return true;
    }

    /**
     * Validates single fixed price policy.
     *
     * @param FixedPricePolicy $policy Policy to validate.
     * @param float $lowerBoundary Value of 'from' field.
     *
     * @return bool TRUE if policy is valid; otherwise, FALSE.
     */
    protected function isPolicyValid($policy, $lowerBoundary)
    {
        return (float)$policy->from === (float)$lowerBoundary && $policy->from < $policy->to && $policy->amount > 0;
    }

    /**
     * Validates percent price policy.
     *
     * @param PercentPricePolicy $policy Policy to validate.
     *
     * @return bool TRUE if policy is valid; otherwise, FALSE.
     */
    protected function validatePercentPricePolicy(PercentPricePolicy $policy)
    {
        return $policy->amount > 0 && ($policy->increase || $policy->amount < 100);
    }
}
