<?php

namespace Packlink\BusinessLogic\Http\DTO\Customs;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class CustomsInvoice
 *
 * @package Packlink\BusinessLogic\Http\DTO\Customs
 */
class CustomsInvoice extends DataTransferObject
{
    /**
     * @var string
     */
    public $invoiceNumber;
    /**
     * @var Sender
     */
    public $sender;
    /**
     * @var Receiver
     */
    public $receiver;
    /**
     * @var InventoryContent[]
     */
    public $inventoriesOfContents;
    /**
     * @var string
     */
    public $reasonForExport;
    /**
     * @var ShipmentDetails
     */
    public $shipmentDetails;
    /**
     * @var Signature
     */
    public $signature;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $result = new static();

        $result->invoiceNumber = static::getDataValue($data, 'invoice_number');
        $result->sender = Sender::fromArray(static::getDataValue($data, 'sender', array()));
        $result->receiver = Receiver::fromArray(static::getDataValue($data, 'receiver', array()));
        $result->inventoriesOfContents = InventoryContent::fromBatch(static::getDataValue($data, 'inventory_of_contents', array()));
        $result->reasonForExport = static::getDataValue($data, 'reason_for_export');
        $result->shipmentDetails = ShipmentDetails::fromArray(static::getDataValue($data, 'shipment_details', array()));
        $result->signature = Signature::fromArray(static::getDataValue($data, 'signature', array()));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $inventories = array();

        foreach ($this->inventoriesOfContents as $inventoryOfContent) {
            $inventories[] = $inventoryOfContent->toArray();
        }

        return array(
            'invoice_number' => $this->invoiceNumber,
            'sender' => $this->sender->toArray(),
            'receiver' => $this->receiver->toArray(),
            'inventory_of_contents' => $inventories,
            'reason_for_export' => $this->reasonForExport,
            'shipment_details' => $this->shipmentDetails->toArray(),
            'signature' => $this->signature->toArray(),
        );
    }
}
