<?php

namespace Packlink\BusinessLogic\ShipmentDocument\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class ShipmentDocument.
 *
 * Unified representation of a shipment-related document (shipping label,
 * customs invoice, ...) for the presentation layer.
 *
 * @package Packlink\BusinessLogic\ShipmentDocument\DTO
 */
class ShipmentDocument extends DataTransferObject
{
    /**
     * Document type. One of \Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType constants.
     *
     * @var string
     */
    private $type;
    /**
     * Link to the document PDF.
     *
     * @var string
     */
    private $link;
    /**
     * Whether the document has been printed/downloaded.
     *
     * @var bool
     */
    private $printed;
    /**
     * Human-readable document name.
     *
     * @var string
     */
    private $name;

    /**
     * ShipmentDocument constructor.
     *
     * @param string $type Document type.
     * @param string $link Link to the PDF.
     * @param bool $printed Whether the document has been printed.
     * @param string $name Human-readable document name.
     */
    public function __construct($type, $link, $printed = false, $name = '')
    {
        $this->type = $type;
        $this->link = $link;
        $this->printed = $printed;
        $this->name = $name;
    }

    /**
     * Transforms raw array data to this DTO instance.
     *
     * @param array $data Raw array data.
     *
     * @return static
     */
    public static function fromArray(array $data)
    {
        return new static(
            static::getDataValue($data, 'type'),
            static::getDataValue($data, 'link'),
            (bool)static::getDataValue($data, 'printed', false),
            static::getDataValue($data, 'name', '')
        );
    }

    /**
     * Transforms this DTO to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'type' => $this->type,
            'link' => $this->link,
            'printed' => $this->printed,
            'name' => $this->name,
        );
    }

    /**
     * Returns the document type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the document type.
     *
     * @param string $type Document type.
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the link to the document PDF.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link to the document PDF.
     *
     * @param string $link Link to the PDF.
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Returns whether the document has been printed.
     *
     * @return bool
     */
    public function isPrinted()
    {
        return $this->printed;
    }

    /**
     * Sets whether the document has been printed.
     *
     * @param bool $printed Printed flag.
     */
    public function setPrinted($printed)
    {
        $this->printed = $printed;
    }

    /**
     * Returns the human-readable document name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the human-readable document name.
     *
     * @param string $name Document name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
