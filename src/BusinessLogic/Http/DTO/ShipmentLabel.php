<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Utility\TimeProvider;

/**
 * Class ShipmentLabel
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ShipmentLabel extends BaseDto
{
    /**
     * Link to PDF.
     *
     * @var string
     */
    private $link;
    /**
     * Is shipment label already printed.
     *
     * @var bool
     */
    private $printed;
    /**
     * Date of creation.
     *
     * @var \DateTime
     */
    private $createTime;

    /**
     * ShipmentLabel constructor.
     *
     * @param string $link Link to PDF.
     * @param bool $printed Whether this label has already been printed.
     * @param int $createTimestamp
     */
    public function __construct($link, $printed = false, $createTimestamp = 0)
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $this->link = $link;
        $this->printed = $printed;
        $this->createTime = $createTimestamp > 0 ? $timeProvider->getDateTime($createTimestamp)
            : $timeProvider->getCurrentLocalTime();
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $batchRaw Raw array data.
     *
     * @return static Transformed entity object.
     */
    public static function fromArray(array $batchRaw)
    {
        return new static(
            static::getValue($batchRaw, 'link'),
            static::getValue($batchRaw, 'printed', false),
            static::getValue($batchRaw, 'createTime', 0)
        );
    }

    /**
     * Transforms shipment label object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'link' => $this->getLink(),
            'printed' => $this->isPrinted(),
            'createTime' => $this->getDateCreatedAsTimestamp(),
        );
    }

    /**
     * Returns link to PDF.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets link to PDF.
     *
     * @param string $link Link to PDF.
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Returns whether this shipment label is already printed.
     *
     * @return bool
     */
    public function isPrinted()
    {
        return $this->printed;
    }

    /**
     * Sets information about whether this shipment label has already been printed.
     *
     * @param bool $printed Is shipment label already printed.
     */
    public function setPrinted($printed)
    {
        $this->printed = $printed;
    }

    /**
     * Returns time and date of creation of this shipment label.
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->createTime;
    }

    /**
     * Returns timestamp of creation of this shipment label.
     *
     * @return int
     */
    public function getDateCreatedAsTimestamp()
    {
        return $this->createTime->getTimestamp();
    }
}
