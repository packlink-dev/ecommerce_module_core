<?php

namespace Logeecom\Tests\BusinessLogic\ShipmentDocument\DTO;

use Packlink\BusinessLogic\ShipmentDocument\DTO\ShipmentDocument;
use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType;

/**
 * Class ShipmentDocumentTest.
 *
 * @package Logeecom\Tests\BusinessLogic\ShipmentDocument\DTO
 */
class ShipmentDocumentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests that toArray returns all four fields in their provided form.
     */
    public function testToArrayReturnsAllFields()
    {
        $document = new ShipmentDocument(
            ShipmentDocumentType::SHIPPING_LABEL,
            'https://example.com/label.pdf',
            true,
            'Shipping label'
        );

        $this->assertEquals(
            array(
                'type' => 'shipping_label',
                'link' => 'https://example.com/label.pdf',
                'printed' => true,
                'name' => 'Shipping label',
            ),
            $document->toArray()
        );
    }

    /**
     * Tests fromArray with a complete payload round-trips through toArray.
     */
    public function testFromArrayWithFullPayload()
    {
        $payload = array(
            'type' => ShipmentDocumentType::CUSTOMS_INVOICE,
            'link' => 'https://example.com/invoice.pdf',
            'printed' => true,
            'name' => 'Customs invoice',
        );

        $document = ShipmentDocument::fromArray($payload);

        $this->assertEquals($payload, $document->toArray());
    }

    /**
     * Tests fromArray fills sensible defaults for missing keys.
     */
    public function testFromArrayAppliesDefaults()
    {
        $document = ShipmentDocument::fromArray(
            array(
                'type' => ShipmentDocumentType::SHIPPING_LABEL,
                'link' => 'https://example.com/label.pdf',
            )
        );

        $this->assertSame(false, $document->isPrinted());
        $this->assertSame('', $document->getName());
    }

    /**
     * Tests fromArray coerces the printed flag to a boolean.
     */
    public function testFromArrayCoercesPrintedToBool()
    {
        $truthy = ShipmentDocument::fromArray(
            array('type' => 'shipping_label', 'link' => 'a', 'printed' => '1')
        );
        $falsy = ShipmentDocument::fromArray(
            array('type' => 'shipping_label', 'link' => 'b', 'printed' => 0)
        );

        $this->assertTrue($truthy->isPrinted());
        $this->assertFalse($falsy->isPrinted());
    }

    /**
     * Tests fromBatch instantiates each payload and preserves order.
     */
    public function testFromBatchInstantiatesAll()
    {
        $batch = ShipmentDocument::fromBatch(
            array(
                array('type' => 'shipping_label', 'link' => 'a'),
                array('type' => 'customs_invoice', 'link' => 'b', 'printed' => true, 'name' => 'CI'),
            )
        );

        $this->assertCount(2, $batch);
        $this->assertEquals('shipping_label', $batch[0]->getType());
        $this->assertEquals('a', $batch[0]->getLink());
        $this->assertEquals('customs_invoice', $batch[1]->getType());
        $this->assertEquals('CI', $batch[1]->getName());
        $this->assertTrue($batch[1]->isPrinted());
    }

    /**
     * Tests every getter/setter pair.
     */
    public function testGettersAndSetters()
    {
        $document = new ShipmentDocument('shipping_label', 'a');

        $document->setType(ShipmentDocumentType::CUSTOMS_INVOICE);
        $document->setLink('https://example.com/x.pdf');
        $document->setPrinted(true);
        $document->setName('renamed');

        $this->assertEquals(ShipmentDocumentType::CUSTOMS_INVOICE, $document->getType());
        $this->assertEquals('https://example.com/x.pdf', $document->getLink());
        $this->assertTrue($document->isPrinted());
        $this->assertEquals('renamed', $document->getName());
    }
}
