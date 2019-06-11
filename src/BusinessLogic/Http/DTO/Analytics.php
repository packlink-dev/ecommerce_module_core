<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class Analytics.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Analytics extends BaseDto
{
    const EVENT_SETUP = 'setup';
    const EVENT_CONFIGURATION = 'api_configuration';
    const EVENT_DRAFT_CREATED = 'import';
    const EVENT_OTHER_SERVICES_DISABLED = 'disable_carriers';
    /**
     * The name of the event. Should be one of the constants from this class.
     *
     * @var string
     */
    public $eventName;
    /**
     * The name of the integrated e-commerce system.
     *
     * @var string
     */
    public $eCommerceName;
    /**
     * The version of the integrated e-commerce system.
     *
     * @var string
     */
    public $eCommerceVersion;
    /**
     * The version of the module.
     *
     * @var string
     */
    private $moduleVersion;

    /**
     * Analytics constructor.
     *
     * @param string $eventName The name of the event.
     * @param string $eCommerceName The name of the integrated e-commerce system.
     * @param string $eCommerceVersion The version of the integrated e-commerce system.
     * @param string $moduleVersion The version of the module.
     */
    public function __construct($eventName, $eCommerceName, $eCommerceVersion, $moduleVersion)
    {
        $this->eventName = $eventName;
        $this->eCommerceName = $eCommerceName;
        $this->eCommerceVersion = $eCommerceVersion;
        $this->moduleVersion = $moduleVersion;
    }

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'event' => $this->eventName,
            'ecommerce' => $this->eCommerceName,
            'ecommerce_version' => $this->eCommerceVersion,
            'module_version' => $this->moduleVersion,
        );
    }
}
