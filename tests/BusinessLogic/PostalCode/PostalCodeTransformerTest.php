<?php

namespace BusinessLogic\PostalCode;

use InvalidArgumentException;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\PostalCode\PostalCodeTransformer;

/**
 * Class PostalCodeTransformerTest
 *
 * @package BusinessLogic\PostalCode
 */
class PostalCodeTransformerTest extends BaseTestWithServices
{
    public function testTransformingUnsupportedCountry()
    {
        $transformedPostalCode = PostalCodeTransformer::transform('DE', '123456789');

        self::assertEquals('123456789', $transformedPostalCode);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testTransformingInvalidPostalCode()
    {
        PostalCodeTransformer::transform('PT', 'AB1234');
    }

    public function testTransformingFormattedPostalCode()
    {
        $transformedPostalCode = PostalCodeTransformer::transform('UK', 'SW1A 1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('NL', '1011 AS');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('PT', '1000-260');
        self::assertEquals('1000-260', $transformedPostalCode);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testTransformingPostalCodeWithSpecialCharacters()
    {
        PostalCodeTransformer::transform('GB', 'SW1Ä1');
    }

    public function testTransformingNonFormattedPostalCode()
    {
        $transformedPostalCode = PostalCodeTransformer::transform('GB', 'SW1A1');
        self::assertEquals('SW 1A1', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('GB', 'SW1A1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('NL', '1011AS');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('PT', '1000260');
        self::assertEquals('1000-260', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('PT', '1000');
        self::assertEquals('1000', $transformedPostalCode);
    }

    public function testTransformingImproperlyFormattedPostalCode()
    {
        $transformedPostalCode = PostalCodeTransformer::transform('GB', 'SW-1A-1');
        self::assertEquals('SW 1A1', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('GB', 'S W1A1AA');
        self::assertEquals('SW1A 1AA', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('NL', '1011A-S');
        self::assertEquals('1011 AS', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('PT', '10 00 260');
        self::assertEquals('1000-260', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('PT', '1 000');
        self::assertEquals('1000', $transformedPostalCode);
    }

    public function testTransformingSpecialCasePostalCode()
    {
        $transformedPostalCode = PostalCodeTransformer::transform('US', '10018-0005');
        self::assertEquals('10018', $transformedPostalCode);

        $transformedPostalCode = PostalCodeTransformer::transform('US', '10018');
        self::assertEquals('10018', $transformedPostalCode);
    }
}