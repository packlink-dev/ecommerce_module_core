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

        $this->assertEquals($expected->weight, $package->weight);
        $this->assertEquals($expected->width, $package->width);
        $this->assertEquals($expected->height, $package->height);
        $this->assertEquals($expected->length, $package->length);
    }

    /**
     * @return array
     */
    public function packagesProvider()
    {
        return array(
            array(
                array(
                    new Package(5.25, 20, 15, 10),
                ),
                new Package(5.25, 20, 15, 10),
            ),
            array(
                array(
                    new Package(5.25, 20, 0, 0),
                ),
                new Package(5.25, 20, self::HEIGHT, self::LENGTH),
            ),
            array(
                array(
                    new Package(5.25, 20, 10, 10),
                    new Package(5.75, 20, 10, 10),
                ),
                new Package(11, self::WIDTH, self::HEIGHT, self::LENGTH),
            ),
            array(
                array(
                    new Package(5.25, 20, 10, 10),
                    new Package(5.75, 20, 10, 10),
                    new Package(),
                ),
                new Package(11 + self::WEIGHT, self::WIDTH, self::HEIGHT, self::LENGTH),
            ),
            array(
                array(
                    new Package(3, 20, 10, 10),
                    new Package(3.246, 20, 10, 10),
                    new Package(),
                    new Package(),
                ),
                new Package(6.246 + 2 * self::WEIGHT, self::WIDTH, self::HEIGHT, self::LENGTH),
            ),
        );
    }
}
