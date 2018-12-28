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
        $indexMap->addIndex(new IntegerIndex('localId'));
        $indexMap->addIndex(new StringIndex('username'));
        $indexMap->addIndex(new StringIndex('email'));
        $indexMap->addIndex(new StringIndex('gender'));
        $indexMap->addIndex(new StringIndex('firstName'));
        $indexMap->addIndex(new StringIndex('lastName'));

        return new EntityConfiguration($indexMap, 'Student');
    }
}
