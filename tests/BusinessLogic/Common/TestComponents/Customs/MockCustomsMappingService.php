<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Customs;

class MockCustomsMappingService extends \Packlink\BusinessLogic\Customs\CustomsMappingService
{

    /**
     * @inheritDoc
     */
    public function getReceiverTaxIdOptions()
    {
        return array(
            'tax_1' => 'Tax 1',
            'tax_2' => 'Tax 2',
            'tax_3' => 'Tax 3',
        );
    }
}