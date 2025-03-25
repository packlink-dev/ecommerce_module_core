<?php

namespace Packlink\BusinessLogic\Controllers\DTO;

use Packlink\BusinessLogic\DTO\FrontDto;

/**
 * Class TaskStatus.
 *
 * @package Packlink\BusinessLogic\Controllers\DTO
 */
class TaskStatus extends FrontDto
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string $status
     */
    public $status;

    /**
     * @var string $message
     */
    public $message;

    /**
     * @var string
     */
    const SUCCESS = 'success';

    /**
     * @var string
     */
    const ERROR = 'error';

    /**
     * Fields for this DTO. Needed for validation and transformation from/to array.
     *
     * @var array
     */
    protected static $fields = array(
        'status',
        'message',
    );
}