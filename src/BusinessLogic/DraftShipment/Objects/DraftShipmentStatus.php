<?php

namespace Packlink\BusinessLogic\DraftShipment\Objects;

use Packlink\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class DraftShipmentStatus.
 *
 * @package Packlink\BusinessLogic\DraftShipment\Objects
 */
class DraftShipmentStatus extends BaseDto
{
    /**
     * Represents the status where send draft shipment task is not created.
     */
    const NOT_QUEUED = 'NOT_QUEUED';
    /**
     * Represents the status where send draft shipment task is created but delayed.
     */
    const DELAYED = 'DELAYED';
    /**
     * A status of the draft shipment.
     *
     * @var string
     */
    public $status;
    /**
     * A latest message related to the draft shipment. Usually, an error message.
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
        $me->status = static::getValue($raw, 'status');
        $me->message = static::getValue($raw, 'message');

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
