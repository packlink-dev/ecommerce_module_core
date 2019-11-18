<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;
use Packlink\BusinessLogic\ShippingMethod\Validation\PricingPolicyValidator;

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
     * Indicates that fixed price by weight range pricing policy is used.
     */
    const PRICING_POLICY_FIXED_PRICE_BY_WEIGHT = 3;
    /**
     * Indicates that fixed price by value range pricing policy is used.
     */
    const PRICING_POLICY_FIXED_PRICE_BY_VALUE = 4;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
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
        'taxClass',
        'isShipToAllCountries',
        'shippingCountries',
    );
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
     * Array of fixed price by weight policy data.
     *
     * @var FixedPricePolicy[]
     */
    protected $fixedPriceByWeightPolicy;
    /**
     * Array of fixed price by value policy data.
     *
     * @var FixedPricePolicy[]
     */
    protected $fixedPriceByValuePolicy;
    /**
     * Percent price policy data.
     *
     * @var PercentPricePolicy
     */
    protected $percentPricePolicy;
    /**
     * Shop tax class.
     *
     * @var mixed
     */
    protected $taxClass;
    /**
     * All services for this shipping method.
     *
     * @var ShippingService[]
     */
    protected $shippingServices = array();
    /**
     * Flag that denotes whether is shipping to all countries allowed.
     *
     * @var boolean
     */
    protected $isShipToAllCountries;
    /**
     * If `isShipToAllCountries` set to FALSe than this array contains list of countries where shipping is allowed.
     *
     * @var array
     */
    protected $shippingCountries;

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        if (isset($data['isShipToAllCountries']) && is_bool($data['isShipToAllCountries'])) {
            $this->isShipToAllCountries = $data['isShipToAllCountries'];
        } else {
            $this->isShipToAllCountries = true;
        }

        if (isset($data['shippingCountries']) && is_array($data['shippingCountries'])) {
            $this->shippingCountries = $data['shippingCountries'];
        } else {
            $this->shippingCountries = array();
        }

        if (!$this->getPricingPolicy()) {
            $this->pricingPolicy = static::PRICING_POLICY_PACKLINK;
        }

        if (!empty($data['fixedPriceByWeightPolicy'])) {
            $this->setFixedPriceByWeightPolicy($this->inflateFixedPricePolicy($data, 'fixedPriceByWeightPolicy'));
        }

        if (!empty($data['fixedPriceByValuePolicy'])) {
            $this->setFixedPriceByValuePolicy($this->inflateFixedPricePolicy($data, 'fixedPriceByValuePolicy'));
        }

        if (!empty($data['percentPricePolicy'])) {
            $this->setPercentPricePolicy(PercentPricePolicy::fromArray($data['percentPricePolicy']));
        }

        if (!empty($data['shippingServices'])) {
            foreach ($data['shippingServices'] as $service) {
                $this->shippingServices[] = ShippingService::fromArray($service);
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

        if ($this->fixedPriceByWeightPolicy) {
            foreach ($this->fixedPriceByWeightPolicy as $fixedPricePolicy) {
                $data['fixedPriceByWeightPolicy'][] = $fixedPricePolicy->toArray();
            }
        }

        if ($this->fixedPriceByValuePolicy) {
            foreach ($this->fixedPriceByValuePolicy as $fixedPricePolicy) {
                $data['fixedPriceByValuePolicy'][] = $fixedPricePolicy->toArray();
            }
        }

        if ($this->percentPricePolicy) {
            $data['percentPricePolicy'] = $this->percentPricePolicy->toArray();
        }

        if ($this->shippingServices) {
            foreach ($this->shippingServices as $service) {
                $data['shippingServices'][] = $service->toArray();
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

        $indexMap->addBooleanIndex('activated')
            ->addBooleanIndex('enabled')
            ->addBooleanIndex('departureDropOff')
            ->addBooleanIndex('destinationDropOff')
            ->addBooleanIndex('national')
            ->addBooleanIndex('expressDelivery')
            ->addStringIndex('carrierName');

        return new EntityConfiguration($indexMap, 'ShippingService');
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
            return $this->getCarrierName() . ' - ' . $this->getDeliveryTime()
                . ($this->isDestinationDropOff() ? ' pick up' : ' delivery');
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
     * Gets shipping method services.
     *
     * @return ShippingService[] Shipping method services.
     */
    public function getShippingServices()
    {
        return $this->shippingServices ?: array();
    }

    /**
     * Sets shipping method services.
     *
     * @param ShippingService[] $shippingServices Shipping method services.
     */
    public function setShippingServices($shippingServices)
    {
        $this->shippingServices = $shippingServices;
    }

    /**
     * Adds shipping method service to the list of services.
     *
     * @param ShippingService $shippingService Shipping method service.
     */
    public function addShippingService($shippingService)
    {
        $this->shippingServices[] = $shippingService;
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
     * Sets data for fixed price by weight policy.
     *
     * @param FixedPricePolicy[] $fixedPricePolicy
     *
     * @throws \InvalidArgumentException
     */
    public function setFixedPriceByWeightPolicy($fixedPricePolicy)
    {
        $fixedPricePolicy = $this->sortFixedPricePolicy($fixedPricePolicy);

        PricingPolicyValidator::validateFixedPricePolicy($fixedPricePolicy);

        $this->percentPricePolicy = null;
        $this->fixedPriceByWeightPolicy = $fixedPricePolicy;
        $this->fixedPriceByValuePolicy = null;
        $this->pricingPolicy = self::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT;
    }

    /**
     * Gets Fixed price by weight policy data.
     *
     * @return FixedPricePolicy[] FixedPricePolicy array.
     */
    public function getFixedPriceByWeightPolicy()
    {
        return $this->fixedPriceByWeightPolicy;
    }

    /**
     * Sets data for fixed price by value policy.
     *
     * @param FixedPricePolicy[] $fixedPricePolicy
     *
     * @throws \InvalidArgumentException
     */
    public function setFixedPriceByValuePolicy($fixedPricePolicy)
    {
        $fixedPricePolicy = $this->sortFixedPricePolicy($fixedPricePolicy);

        PricingPolicyValidator::validateFixedPricePolicy($fixedPricePolicy, true);

        $this->percentPricePolicy = null;
        $this->fixedPriceByWeightPolicy = null;
        $this->fixedPriceByValuePolicy = $fixedPricePolicy;
        $this->pricingPolicy = self::PRICING_POLICY_FIXED_PRICE_BY_VALUE;
    }

    /**
     * Gets Fixed price by value policy data.
     *
     * @return FixedPricePolicy[] FixedPricePolicy array.
     */
    public function getFixedPriceByValuePolicy()
    {
        return $this->fixedPriceByValuePolicy;
    }

    /**
     * Sets Percent pricing policy.
     *
     * @param PercentPricePolicy $percentPricePolicy Percent price policy data.
     */
    public function setPercentPricePolicy($percentPricePolicy)
    {
        PricingPolicyValidator::validatePercentPricePolicy($percentPricePolicy);

        $this->percentPricePolicy = $percentPricePolicy;
        $this->fixedPriceByWeightPolicy = null;
        $this->fixedPriceByValuePolicy = null;
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
        $this->fixedPriceByWeightPolicy = null;
        $this->fixedPriceByValuePolicy = null;
        $this->pricingPolicy = self::PRICING_POLICY_PACKLINK;
    }

    /**
     * Gets tax class.
     *
     * @return mixed Shop tax class.
     */
    public function getTaxClass()
    {
        return $this->taxClass;
    }

    /**
     * Sets tax class.
     *
     * @param mixed $taxClass Shop tax class.
     */
    public function setTaxClass($taxClass)
    {
        $this->taxClass = $taxClass;
    }

    /**
     * Sets list of allowed destination countries.
     *
     * @param array $shippingCountries List of allowed destination countries.
     */
    public function setShippingCountries(array $shippingCountries) {
        $this->shippingCountries = $shippingCountries;
    }

    /**
     * Retrieves list of allowed destination countries.
     *
     * @return array List of allowed destination countries.
     */
    public function getShippingCountries()
    {
        return $this->shippingCountries;
    }

    /**
     * Retrieves flag that denotes whether shipping to all countries is enabled.
     *
     * @return bool Flag that denotes whether shipping to all countries is enabled.
     */
    public function isShipToAllCountries()
    {
        return $this->isShipToAllCountries;
    }

    /**
     * Sets flag that denotes whether shipping to all countries is enabled.
     *
     * @param boolean $isShipToAllCountries Flag that denotes whether shipping to all countries is enabled.
     */
    public function setShipToAllCountries($isShipToAllCountries)
    {
        $this->isShipToAllCountries = $isShipToAllCountries;
    }

    /**
     * Sorts fixed price policies by ranges.
     *
     * @param FixedPricePolicy[] $fixedPricePolicy
     *
     * @return FixedPricePolicy[] Sorted array.
     */
    protected function sortFixedPricePolicy($fixedPricePolicy)
    {
        usort(
            $fixedPricePolicy,
            function (FixedPricePolicy $first, FixedPricePolicy $second) {
                return $first->from > $second->from;
            }
        );

        return $fixedPricePolicy;
    }

    /**
     * Inflates fixed price policies from array.
     *
     * @param array $data Source array.
     * @param string $type Type of fixed price policy as a key for the source array.
     *
     * @return FixedPricePolicy[]
     */
    protected function inflateFixedPricePolicy($data, $type)
    {
        $policies = array();
        foreach ($data[$type] as $fixedPricePolicy) {
            $policies[] = FixedPricePolicy::fromArray($fixedPricePolicy);
        }

        return $policies;
    }
}
