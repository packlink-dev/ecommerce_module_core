<?php

namespace Logeecom\Tests\BusinessLogic\ShipmentDocument;

use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType;

/**
 * Class ShipmentDocumentTypeTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShipmentDocument
 */
class ShipmentDocumentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that getAll returns exactly the two known document types.
     */
    public function testGetAllReturnsBothTypes()
    {
        $all = ShipmentDocumentType::getAll();

        $this->assertCount(2, $all);
        $this->assertContains(ShipmentDocumentType::SHIPPING_LABEL, $all);
        $this->assertContains(ShipmentDocumentType::CUSTOMS_INVOICE, $all);
    }

    /**
     * Tests human-readable labels for known types.
     */
    public function testGetLabelKnownTypes()
    {
        $this->assertEquals(
            'Shipping label',
            ShipmentDocumentType::getLabel(ShipmentDocumentType::SHIPPING_LABEL)
        );
        $this->assertEquals(
            'Customs invoice',
            ShipmentDocumentType::getLabel(ShipmentDocumentType::CUSTOMS_INVOICE)
        );
    }

    /**
     * Tests that an unknown type falls back to the input string.
     */
    public function testGetLabelFallsBackToInputForUnknown()
    {
        $this->assertEquals('foo', ShipmentDocumentType::getLabel('foo'));
        $this->assertEquals('', ShipmentDocumentType::getLabel(''));
    }
}
