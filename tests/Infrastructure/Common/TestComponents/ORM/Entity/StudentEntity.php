<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * Class StudentEntity.
 *
 * @package Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\Entity
 */
class StudentEntity extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * @var int
     */
    public $localId;
    /**
     * @var string
     */
    public $username;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $firstName;
    /**
     * @var string
     */
    public $lastName;
    /**
     * @var string
     */
    public $gender;
    /**
     * @var array
     */
    public $demographics;
    /**
     * @var array
     */
    public $addresses;
    /**
     * @var array
     */
    public $alerts;
    /**
     * @var array
     */
    public $schoolEnrollment;
    /**
     * @var array
     */
    public $contact;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'localId',
        'username',
        'email',
        'firstName',
        'lastName',
        'gender',
        'demographics',
        'addresses',
        'alerts',
        'schoolEnrollment',
        'contact',
    );

    /**
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addIntegerIndex('localId')
            ->addStringIndex('username')
            ->addStringIndex('email')
            ->addStringIndex('gender')
            ->addStringIndex('firstName')
            ->addStringIndex('lastName');

        return new EntityConfiguration($indexMap, 'Student');
    }
}
