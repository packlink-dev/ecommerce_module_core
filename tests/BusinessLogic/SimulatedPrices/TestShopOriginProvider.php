<?php

namespace Logeecom\Tests\BusinessLogic\SimulatedPrices;

use Packlink\BusinessLogic\SimulatedPrices\Interfaces\ShopOriginProvider;

/**
 * Class TestShopOriginProvider.
 *
 * @package Logeecom\Tests\BusinessLogic\SimulatedPrices
 */
class TestShopOriginProvider implements ShopOriginProvider
{
    /**
     * @var array|null
     */
    public $origin;
    /**
     * @var \Exception|null
     */
    public $exception;
    /**
     * @var int
     */
    public $calls = 0;

    /**
     * @inheritDoc
     */
    public function getOrigin()
    {
        $this->calls++;
        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->origin;
    }
}
