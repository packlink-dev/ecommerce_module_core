<?php

namespace Logeecom\Tests\BusinessLogic\ShippingMethod;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;

/**
 * Class PackageTransformerTest
 *
 * @package Logeecom\Tests\BusinessLogic\ShippingMethod
 */
class PackageTransformerTest extends BaseTestWithServices
{
    /**
     * Default weight.
     */
    const WEIGHT = 10.5;
    /**
     * Default width.
     */
    const WIDTH = 20;
    /**
     * Default height.
     */
    const HEIGHT = 20;
    /**
     * Default length.
     */
    const LENGTH = 20;
    /**
     * @var PackageTransformer
     */
    private $transformer;
    /**
     * @var ParcelInfo
     */
    private $defaultParcel;

    /**
     * @before
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    protected function before()
    {
        parent::before();

        $this->transformer = PackageTransformer::getInstance();

        $this->defaultParcel = ParcelInfo::fromArray(
            array(
                'weight' => self::WEIGHT,
                'width' => self::WIDTH,
                'height' => self::HEIGHT,
                'length' => self::LENGTH,
            )
        );

        $this->shopConfig->setDefaultParcel($this->defaultParcel);
    }

    public function testEmptyPackages()
    {
        $package = $this->transformer->transform();

        $this->assertEquals($this->defaultParcel->weight, $package->weight);
        $this->assertEquals($this->defaultParcel->width, $package->width);
        $this->assertEquals($this->defaultParcel->height, $package->height);
        $this->assertEquals($this->defaultParcel->length, $package->length);
    }

    /**
     * @dataProvider packagesProvider
     *
     * @param Package[] $packages
     * @param Package $expected
     */
    public function testTransformPackages(array $packages, Package $expected)
    {
        $package = $this->transformer->transform($packages);

        $this->assertEquals($expected->weight, $package->weight, '', 0.0001);
        $this->assertEquals($expected->width, $package->width, '', 0.0001);
        $this->assertEquals($expected->height, $package->height, '', 0.0001);
        $this->assertEquals($expected->length, $package->length, '', 0.0001);
    }

    public function testMultipleItemsKeepTheLongestSide()
    {
        // A 65 cm long item and an item without dimensions or weight.
        $package = $this->transformer->transform(array(new Package(3, 20, 15, 65), new Package()));

        $this->assertEquals(
            array('width' => 22, 'height' => 20, 'length' => 65, 'weight' => 13.5),
            $package->toArray()
        );
    }

    /**
     * @return array
     */
    public static function packagesProvider()
    {
        return array(
            'single item keeps its dimensions' => array(
                array(
                    new Package(5.25, 20, 15, 10),
                ),
                new Package(5.25, 20, 15, 10),
            ),
            'single item gets default sides only where missing' => array(
                array(
                    new Package(5.25, 20, 0, 0),
                ),
                new Package(5.25, 20, self::HEIGHT, self::LENGTH),
            ),
            'two items grow the shortest side to fit the summed volume' => array(
                array(
                    new Package(5.25, 20, 10, 10),
                    new Package(5.75, 20, 10, 10),
                ),
                new Package(11, 20, 20, 10),
            ),
            'item without dimensions counts as the default parcel' => array(
                array(
                    new Package(5.25, 20, 10, 10),
                    new Package(5.75, 20, 10, 10),
                    new Package(),
                ),
                new Package(11 + self::WEIGHT, 30, 20, 20),
            ),
            'several items without dimensions' => array(
                array(
                    new Package(3, 20, 10, 10),
                    new Package(3.246, 20, 10, 10),
                    new Package(),
                    new Package(),
                ),
                new Package(6.246 + 2 * self::WEIGHT, 50, 20, 20),
            ),
            'long item is not shortened to the default length' => array(
                array(
                    new Package(3, 20, 15, 65),
                    new Package(),
                ),
                new Package(3 + self::WEIGHT, 27500 / (20 * 65), 20, 65),
            ),
            'box of the longest sides already fits the summed volume' => array(
                array(
                    new Package(1, 60, 10, 10),
                    new Package(1, 10, 60, 10),
                ),
                new Package(2, 60, 60, 10),
            ),
        );
    }
}
