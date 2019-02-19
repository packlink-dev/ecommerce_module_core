<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Packlink\BusinessLogic\Http\DTO\Draft\AdditionalData;
use Packlink\BusinessLogic\Http\DTO\Draft\Address;
use Packlink\BusinessLogic\Http\DTO\Draft\DraftPrice;

/**
 * Class Draft.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class Draft extends BaseDto
{
    /**
     * Unique user identifier.
     *
     * @var string
     */
    public $userId;
    /**
     * Unique client identifier.
     *
     * @var string
     */
    public $clientId;
    /**
     * Unique platform identifier.
     *
     * @var string
     */
    public $platform = 'PRO';
    /**
     * Unique platform country identifier.
     *
     * @var string
     */
    public $platformCountry;
    /**
     * Shipment's source referral.
     *
     * @var string
     */
    public $source;
    /**
     * Shipment's source address.
     *
     * @var Address
     */
    public $from;
    /**
     * Shipment's destination address.
     *
     * @var Address
     */
    public $to;
    /**
     * Name of service.
     *
     * @var string
     */
    public $serviceName;
    /**
     * Name of carrier.
     *
     * @var string
     */
    public $carrierName;
    /**
     * Specification of packages that are being sent.
     *
     * @var Package[]
     */
    public $packages = array();
    /**
     * Object to include additional data of the shipment.
     *
     * @var AdditionalData
     */
    public $additionalData;
    /**
     * Specifies the shipping service.
     *
     * @var string
     */
    public $serviceId;
    /**
     * Specifies the collection date (Format: YYYY/MM/DD).
     *
     * @var string
     */
    public $collectionDate;
    /**
     * Specifies the collection time (Format: HH:MM-HH:MM).
     *
     * @var string
     */
    public $collectionTime;
    /**
     * Destination's drop-off point unique identifier.
     *
     * @var string
     */
    public $dropOffPointId;
    /**
     * Description of shipment's content.
     *
     * @var array
     */
    public $content;
    /**
     * Value of shipment's content.
     *
     * @var float
     */
    public $contentValue;
    /**
     * This field indicates whether the shipment content is second hand (value = true) or new (value = false).
     *
     * @var bool
     */
    public $contentSecondHand = false;
    /**
     * Personal, unique identifier for your shipment. Can be used to save e.g. transaction number.
     * Max 50 chars
     *
     * @var string
     */
    public $shipmentCustomReference;
    /**
     * Determines if sender has selected priority customer.
     *
     * @var bool
     */
    public $priority;
    /**
     * Indicates the currency of payment.
     *
     * @var string
     */
    public $contentValueCurrency;
    /**
     * Information about price of shipments.
     *
     * @var DraftPrice
     */
    public $price;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array(
            'user_id' => $this->userId,
            'client_id' => $this->clientId,
            'platform' => $this->platform,
            'platform_country' => $this->platformCountry,
            'source' => $this->source,
            'service' => $this->serviceName,
            'carrier' => $this->carrierName,
            'service_id' => $this->serviceId,
            'collection_date' => $this->collectionDate,
            'collection_time' => $this->collectionTime,
            'dropoff_point_id' => $this->dropOffPointId,
            'content' => $this->getContent(),
            'contentvalue' => round($this->contentValue, 2),
            'content_second_hand' => $this->contentSecondHand,
            'shipment_custom_reference' => $this->shipmentCustomReference,
            'priority' => $this->priority,
            'contentValue_currency' => $this->contentValueCurrency,
        );

        if ($this->from) {
            $result['from'] = $this->from->toArray();
        }

        if ($this->to) {
            $result['to'] = $this->to->toArray();
        }

        if ($this->additionalData) {
            $result['additional_data'] = $this->additionalData->toArray();
        }

        if (!empty($this->packages)) {
            $result['packages'] = array();
            foreach ($this->packages as $package) {
                $result['packages'][] = $package->toArray();
            }
        }

        return $result;
    }

    /**
     * Gets valid content string.
     *
     * @return string Content of the packages as string description.
     */
    private function getContent()
    {
        $forbiddenCharacters = array(
            ';',
            ':',
            '%',
            '&',
            '/',
            'º',
            'ª',
            '€',
            '$',
            '@',
            '#',
            '(',
            ')',
            '=',
            '?',
            '¿',
            '¡',
            '!',
            '\\',
            '\'',
            '`',
            '´',
            '^',
            '*',
            'Ê',
            'è',
        );

        $content = implode(', ', $this->content);

        return substr(str_replace($forbiddenCharacters, '', $content), 0, 60);
    }
}
