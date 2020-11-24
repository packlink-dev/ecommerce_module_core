<?php

namespace BusinessLogic\PostalCode;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\PostalCode\PostalCodeTransformer;

/**
 * Class PostalCodeTransformerTest
 *
 * @package BusinessLogic\PostalCode
 */
class PostalCodeTransformerTest extends BaseTestWithServices
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        TestServiceRegister::registerService(
            PostalCodeTransformer::CLASS_NAME,
            function () {
                return new PostalCodeTransformer();
            }
        );
    }

    public function testTransformingUnsupportedCountry()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);
        $transformedPostalCode = $postalCodeTransformer->transform('DE', '123456789');

        self::assertEquals('123456789', $transformedPostalCode);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTransformingInvalidPostalCode()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);
        $postalCodeTransformer->transform('PT', 'AB1234');
    }

    public function testTransformingFormattedPostalCode()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);

        $transformedPostalCode = $postalCodeTransformer->transform('UK', 'SW1A 1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('NL', '1011 AS');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('PT', '1000-260');
        self::assertEquals('1000-260', $transformedPostalCode);
    }

    public function testTransformingNonFormattedPostalCode()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);

        $transformedPostalCode = $postalCodeTransformer->transform('UK', 'SW1A1');
        self::assertEquals('SW 1A1', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('UK', 'SW1A1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('NL', '1011AS');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('PT', '1000260');
        self::assertEquals('1000-260', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('PT', '1000');
        self::assertEquals('1000', $transformedPostalCode);
    }

    public function testTransformingImproperlyFormattedPostalCode()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);

        $transformedPostalCode = $postalCodeTransformer->transform('UK', 'SW-1A-1');
        self::assertEquals('SW 1A1', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('UK', 'S W1A1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('NL', '1011A-S');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('PT', '10 00 260');
        self::assertEquals('1000-260', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('PT', '1 000');
        self::assertEquals('1000', $transformedPostalCode);
    }

    public function testTransformingSpecialCasePostalCode()
    {
        /** @var PostalCodeTransformer $postalCodeTransformer */
        $postalCodeTransformer = TestServiceRegister::getService(PostalCodeTransformer::CLASS_NAME);
        $transformedPostalCode = $postalCodeTransformer->transform('US', '10018-0005');

        self::assertEquals('10018', $transformedPostalCode);

        $transformedPostalCode = $postalCodeTransformer->transform('US', '10018');

        self::assertEquals('10018', $transformedPostalCode);
    }
}
