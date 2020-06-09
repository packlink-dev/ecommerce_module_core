<?php

namespace Packlink\BusinessLogic\ShipmentDraft\Objects;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentDraftStatus.
 *
 * @package Packlink\BusinessLogic\ShipmentDraft\Objects
 */
class ShipmentDraftStatus extends DataTransferObject
{
    /**
     * Represents the status where create shipment draft task is not created.
     */
    const NOT_QUEUED = 'NOT_QUEUED';
    /**
     * Represents the status where create shipment draft task is created but delayed.
     */
    const DELAYED = 'DELAYED';
    /**
     * A status of the shipment draft.
     *
     * @var string
     */
    public $status;
    /**
     * A latest message related to the shipment draft. Usually, an error message.
     *
     * @var string
     */
    public $message;

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $me = new static();
        $me->status = static::getDataValue($raw, 'status');
        $me->message = static::getDataValue($raw, 'message');

        return $me;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'status' => $this->status,
            'message' => $this->message,
        );
    }
}
