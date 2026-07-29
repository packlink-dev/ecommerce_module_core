<?php

namespace Packlink\BusinessLogic\Http\DTO\DDP;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentProductsRequestItem. One shipment entry of the /pro/shipments/products request.
 *
 * Any additional shipment fields required by the Packlink API reference for this endpoint
 * must stay isolated in this class.
 *
 * @package Packlink\BusinessLogic\Http\DTO\DDP
 */
class ShipmentProductsRequestItem extends DataTransferObject
{
    /**
     * @var string
     */
    public $serviceId;
    /**
     * @var float
     */
    public $contentValue;
    /**
     * @var string
     */
    public $contentValueCurrency;
    /**
     * @var string
     */
    public $customsInvoiceId;

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'service_id' => $this->serviceId,
            'contentvalue' => round($this->contentValue, 2),
            'contentValue_currency' => $this->contentValueCurrency,
            'customs' => array(
                'customs_invoice_id' => $this->customsInvoiceId,
            ),
        );
    }
}
