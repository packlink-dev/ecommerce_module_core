<?php

namespace Packlink\BusinessLogic\Http\DTO\Draft;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class Customs
 *
 * @package Packlink\BusinessLogic\Http\DTO\Draft
 */
class Customs extends DataTransferObject
{
    /**
     * @var string
     */
    public $customsInvoiceId;

    public function toArray()
    {
        return array(
            'customs_invoice_id' => $this->customsInvoiceId,
        );
    }
}
