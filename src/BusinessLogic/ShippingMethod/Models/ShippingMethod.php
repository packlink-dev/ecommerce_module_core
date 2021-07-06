<?php

namespace Packlink\BusinessLogic\ShippingMethod\Models;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;

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
        'usePacklinkPriceIfNotInRange',
        'taxClass',
        'isShipToAllCountries',
        'shippingCountries',
        'currency',
        'fixedPrices',
        'systemDefaults',
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
     * An array of pricing policies used.
     *
     * @var array
     */
    protected $pricingPolicies = array();
    /**
     * Indicates whether to use Packlink prices when none of the set pricing policies is in range.
     *
     * @var bool
     */
    protected $usePacklinkPriceIfNotInRange = true;
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
     * Flag that denotes whether is shipping to all countries selected.
     *
     * @var boolean
     */
    protected $isShipToAllCountries;
    /**
     * If `isShipToAllCountries` set to FALSE, then this array contains list of countries where shipping is allowed.
     *
     * @var array
     */
    protected $shippingCountries;
    /**
     * Shipping method currency.
     * The value represents a currency code (ex. EUR, USD, GBP).
     *
     * @var string
     */
    protected $currency;
    /**
     * Key-value pairs of system info IDs and fixed prices in the default currency
     * (used in multi-store environments when the service currency does not match the system currency).
     *
     * @var array
     */
    public $fixedPrices;
    /**
     * Key-value pairs of system info IDs and whether they are using default pricing policy
     * (used in multi-store environments when the service currency does not match the system currency).
     *
     * @var array
     */
    public $systemDefaults;

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        if (isset($data['usePacklinkPriceIfNotInRange']) && is_bool($data['usePacklinkPriceIfNotInRange'])) {
            $this->usePacklinkPriceIfNotInRange = $data['usePacklinkPriceIfNotInRange'];
        } else {
            $this->usePacklinkPriceIfNotInRange = true;
        }

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

        if (isset($data['pricingPolicies'])) {
            $this->pricingPolicies = FrontDtoFactory::getFromBatch(
                ShippingPricePolicy::CLASS_KEY,
                $data['pricingPolicies']
            );
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

        if ($this->pricingPolicies) {
            foreach ($this->pricingPolicies as $policy) {
                $data['pricingPolicies'][] = $policy->toArray();
            }
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
     * Sets new pricing policy.
     *
     * @param ShippingPricePolicy $policy
     */
    public function addPricingPolicy(ShippingPricePolicy $policy)
    {
        $this->pricingPolicies[] = $policy;
    }

    /**
     * Gets Percent pricing policy data.
     *
     * @return ShippingPricePolicy[] PercentPricePolicy data.
     */
    public function getPricingPolicies()
    {
        return $this->pricingPolicies;
    }

    /**
     * Removes all pricing policies.
     */
    public function resetPricingPolicies()
    {
        $this->pricingPolicies = array();
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
    public function setShippingCountries(array $shippingCountries)
    {
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
     * Retrieves a flag that denotes whether shipping to all countries is enabled.
     *
     * @return bool Flag that denotes whether shipping to all countries is enabled.
     */
    public function isShipToAllCountries()
    {
        return $this->isShipToAllCountries;
    }

    /**
     * Sets a flag that denotes whether shipping to all countries is enabled.
     *
     * @param boolean $isShipToAllCountries Flag that denotes whether shipping to all countries is enabled.
     */
    public function setShipToAllCountries($isShipToAllCountries)
    {
        $this->isShipToAllCountries = $isShipToAllCountries;
    }

    /**
     * Retrieves a flag that denotes whether Packlink price should be used when all policies are out of range.
     *
     * @return bool Flag that denotes whether Packlink price should be used when all policies are out of range.
     */
    public function isUsePacklinkPriceIfNotInRange()
    {
        return $this->usePacklinkPriceIfNotInRange;
    }

    /**
     * Sets a flag that denotes whether Packlink price should be used when all policies are out of range.
     *
     * @param bool $usePacklinkPriceIfNotInRange Out-of-range behavior.
     */
    public function setUsePacklinkPriceIfNotInRange($usePacklinkPriceIfNotInRange)
    {
        $this->usePacklinkPriceIfNotInRange = $usePacklinkPriceIfNotInRange;
    }

    /**
     * Returns shipping method currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets shipping method currency.
     *
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Returns fixed prices.
     *
     * @return array
     */
    public function getFixedPrices()
    {
        return $this->fixedPrices;
    }

    /**
     * Sets fixed prices.
     *
     * @param array $fixedPrices
     */
    public function setFixedPrices($fixedPrices)
    {
        $this->fixedPrices = $fixedPrices;
    }

    /**
     * Returns system defaults.
     *
     * @return array
     */
    public function getSystemDefaults()
    {
        return $this->systemDefaults;
    }

    /**
     * Sets system defaults.
     *
     * @param array $systemDefaults
     */
    public function setSystemDefaults($systemDefaults)
    {
        $this->systemDefaults = $systemDefaults;
    }
}
