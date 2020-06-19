<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class AdditionalData
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class AdditionalData extends DataTransferObject
{
    /**
     * Value of the postal zone id corresponding to the origin postal code.
     *
     * @var string
     */
    public $postalZoneIdFrom;
    /**
     * Value of the postal zone id corresponding to the destination postal code.
     *
     * @var string
     */
    public $postalZoneIdTo;
    /**
     * Name of shipping service.
     *
     * @var string
     */
    public $shippingServiceName;
    /**
     * Origin zip code id. Note that this is not the zip code: the same code can be
     * present in different countries, each representing a different zone and thus
     * having a different zip_code_id.
     *
     * @var string
     */
    public $zipCodeIdFrom;
    /**
     * Destination zip code id. Note that this is not the zip code: the same code
     * can be present in different countries, each representing a different zone
     * and thus having a different zip_code_id.
     *
     * @var string
     */
    public $zipCodeIdTo;
    /**
     * Identifier of the default warehouse.
     *
     * @var string
     */
    public $selectedWarehouseId;
    /**
     * Items contained in the draft.
     *
     * @var DraftItem[]
     */
    public $items;
    /**
     * Name of the postal zone.
     *
     * @var string
     */
    public $postalZoneNameTo;
    /**
     * List of the parcels contained in the draft.
     *
     * @var string[]
     */
    public $parcelIds = array();
    /**
     * Order ID.
     *
     * @var string
     */
    public $orderId;
    /**
     * Seller user ID.
     *
     * @var string
     */
    public $sellerUserId;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        $result = array(
            'postal_zone_id_from' => $this->postalZoneIdFrom,
            'postal_zone_id_to' => $this->postalZoneIdTo,
            'shipping_service_name' => $this->shippingServiceName,
            'zip_code_id_from' => $this->zipCodeIdFrom,
            'zip_code_id_to' => $this->zipCodeIdTo,
            'selectedWarehouseId' => $this->selectedWarehouseId,
            'parcel_Ids' => $this->parcelIds,
            'postal_zone_name_to' => $this->postalZoneNameTo,
            'order_id' => $this->orderId,
            'seller_user_id' => $this->sellerUserId,
        );

        if (!empty($this->items)) {
            $result['items'] = array();
            foreach ($this->items as $item) {
                $result['items'][] = $item->toArray();
            }
        }

        return $result;
    }
}
