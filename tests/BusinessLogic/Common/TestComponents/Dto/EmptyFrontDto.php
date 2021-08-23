<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class EmptyFrontDto.
 *
 * @package Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto
 */
class EmptyFrontDto extends FrontDto
{
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
