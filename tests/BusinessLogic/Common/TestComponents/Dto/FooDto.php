<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class FooDto.
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto
 */
class FooDto extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Unique class key.
     */
    const CLASS_KEY = 'foo';
    /**
     * @var string
     */
    public $foo;
    /**
     * @var string
     */
    public $bar;
    /**
     * @var array
     */
    protected static $fields = array('foo', 'bar');
    /**
     * @var array
     */
    protected static $requiredFields = array('foo', 'bar');

    /**
     * Creates instance of this class.
     *
     * @param array $data
     *
     * @return static
     *
     * @noinspection PhpDocSignatureInspection
     */
    public static function create(array $data)
    {
        return new self();
    }
}
