<?php

namespace Logeecom\Tests\Common\TestComponents\ORM\Entity;

use Logeecom\Infrastructure\ORM\Configuration\Indexes\IntegerIndex;
use Logeecom\Infrastructure\ORM\Configuration\Indexes\StringIndex;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entities\Entity;
use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;

/**
 * Class StudentEntity.
 *
 * @package Logeecom\Tests\Common\TestComponents\ORM\Entity
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
     * Returns entity configuration object
     *
     * @return EntityConfiguration
     */
    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addIndex(new StringIndex('localId', 1));
        $indexMap->addIndex(new IntegerIndex('username', 2));
        $indexMap->addIndex(new IntegerIndex('email', 3));
        $indexMap->addIndex(new IntegerIndex('gender', 4));
        $indexMap->addIndex(new IntegerIndex('firstName', 5));
        $indexMap->addIndex(new IntegerIndex('lastName', 6));

        return new EntityConfiguration($indexMap, 'Student', 'Student');
    }
}
