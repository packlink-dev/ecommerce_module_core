<?php
/**
 * Created by PhpStorm.
 * User: kostandin
 * Date: 16.12.18.
 * Time: 13.45
 */

namespace Logeecom\Tests\Common;

use Logeecom\Infrastructure\ServiceRegister;

/**
 * Class TestServiceRegister.
 *
 * @package Logeecom\Tests\Common
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
