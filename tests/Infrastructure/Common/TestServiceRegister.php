<?php

namespace Logeecom\Tests\Infrastructure\Common;

use Logeecom\Infrastructure\ServiceRegister;

/**
 * Class TestServiceRegister.
 *
 * @package Logeecom\Tests\Infrastructure\Common
 */
class TestServiceRegister extends ServiceRegister
{
    /**
     * TestServiceRegister constructor.
     *
     * @inheritdoc
     */
    public function __construct(array $services = array())
    {
        // changing visibility so that services could be reset in tests.
        parent::__construct($services);
    }
}
