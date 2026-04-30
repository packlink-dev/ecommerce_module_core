<?php

namespace Logeecom\Tests\BusinessLogic\ShipmentDocument;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType;

/**
 * Class ShipmentDocumentTypeTest.
 *
 * Extends BaseTestWithServices because `getLabel()` calls `Translator::translate`,
 * which resolves `LabelServiceInterface` from the service register. The base
 * harness wires that service via `FileResolverService`.
 *
 * @package Logeecom\Tests\BusinessLogic\ShipmentDocument
 */
class ShipmentDocumentTypeTest extends BaseTestWithServices
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
     * Tests that known types resolve to a non-empty human-readable label.
     * Uses the localised string (via Translator) when available, falling back
     * to the hardcoded English defaults.
     */
    public function testGetLabelKnownTypes()
    {
        $this->assertNotEmpty(ShipmentDocumentType::getLabel(ShipmentDocumentType::SHIPPING_LABEL));
        $this->assertNotEmpty(ShipmentDocumentType::getLabel(ShipmentDocumentType::CUSTOMS_INVOICE));
        $this->assertNotEquals(
            ShipmentDocumentType::SHIPPING_LABEL,
            ShipmentDocumentType::getLabel(ShipmentDocumentType::SHIPPING_LABEL),
            'Known type must not return its raw constant value as its display label.'
        );
    }

    /**
     * Tests that an unknown type falls back to the input string. Unknown types
     * short-circuit before the Translator lookup, so this works without the
     * harness — but we still extend it to keep the suite uniform.
     */
    public function testGetLabelFallsBackToInputForUnknown()
    {
        $this->assertEquals('foo', ShipmentDocumentType::getLabel('foo'));
        $this->assertEquals('', ShipmentDocumentType::getLabel(''));
    }
}
