<?php

namespace Logeecom\Tests\BusinessLogic\Utility;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Utility\DtoValidator;

class DtoValidatorTest extends BaseTestWithServices
{
    public function testValidNumbers()
    {
        $numbers = array(
            '754-3010',
            '7543010',
            '754 3010',
            '754.3010',
            '754+3010',
            '7543010',
            '-7543010',
            '.7543010',
            '+754 3010',
            '(541) 754-3010',
            '(541) (754)-3010',
            '(541) 754-(3010)',
            '(541754-3010)',
            '+1-541-754-3010',
            '1-541-754-3010',
            '001-541-754-3010',
            '191 541 754 3010',
            '636-48018',
            '(089) / 636-48018',
            '+49-89-636-48018',
            '19-49-89-636-48018',
        );

        foreach ($numbers as $number) {
            $this->assertTrue(DtoValidator::isPhoneValid($number), "[$number] failed phone validation.");
        }
    }

    public function testInvalidPhoneNumbers()
    {
        $numbers = array(
            '',
            'a754-3010',
            '75b43010',
            '754 30c10',
            '754.3010d',
            '#754+3010',
            '75f43010',
            '~-7543010',
            '.75@43010',
            '+754 &3010',
            '(541) 754-3010\\',
            '(541) (754)-3010?',
            '(541) 754-(3010)!',
            '@@(541754-3010)',
            '#+1-541-754-3010',
            '1-*541-754-3010',
            '001-541-754-3010\'',
            '"191 541 754 3010',
            '\\636\\-48018',
            '(089)` / 636-48018',
            '`+49`-89-636-48018',
            '?19-49-89-636-48018',
        );

        foreach ($numbers as $number) {
            $this->assertFalse(DtoValidator::isPhoneValid($number), "[$number] should not pass phone validation.");
        }
    }
}