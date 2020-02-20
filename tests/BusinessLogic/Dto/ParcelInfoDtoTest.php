<?php

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;

/**
 * Class ParcelInfoDtoTest.
 *
 * @package BusinessLogic\Dto
 */
class ParcelInfoDtoTest extends BaseDtoTest
{
    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testFromArray()
    {
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        $parcelsData = json_decode(file_get_contents(__DIR__ . '/../Common/ApiResponses/parcels.json'), true);

        /** @var ParcelInfo[] $parcels */
        $parcels = TestFrontDtoFactory::getFromBatch(ParcelInfo::CLASS_KEY, $parcelsData);
        $this->assertCount(2, $parcels);
        $this->assertEquals(2, $parcels[0]->weight);
        $this->assertEquals(3, $parcels[0]->length);
        $this->assertEquals(6, $parcels[0]->height);
        $this->assertEquals(3, $parcels[0]->width);

        $this->assertEquals(5, $parcels[1]->weight);
        $this->assertEquals(6, $parcels[1]->length);
        $this->assertEquals(3, $parcels[1]->height);
        $this->assertEquals(2, $parcels[1]->width);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testMissingFields()
    {
        $errors = null;
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        try {
            TestFrontDtoFactory::get(ParcelInfo::CLASS_KEY, array());
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        $this->assertCount(4, $errors, 'All mandatory fields must be validated.');
        foreach ($errors as $error) {
            $this->assertEquals(ValidationError::ERROR_REQUIRED_FIELD, $error->code);
        }
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testInvalidFields()
    {
        $errors = null;
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);

        try {
            TestFrontDtoFactory::get(
                ParcelInfo::CLASS_KEY,
                array('width' => 'a', 'length' => 'b', 'height' => 'c', 'weight' => '2a')
            );
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        $this->assertCount(4, $errors, 'All invalid fields must be validated.');
        foreach ($errors as $error) {
            $this->assertEquals(ValidationError::ERROR_INVALID_FIELD, $error->code);
        }

        $this->assertEquals('width', $errors[0]->field);
        $this->assertEquals('length', $errors[1]->field);
        $this->assertEquals('height', $errors[2]->field);
        $this->assertEquals('weight', $errors[3]->field);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testNegativeFields()
    {
        $errors = null;
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);

        try {
            TestFrontDtoFactory::get(
                ParcelInfo::CLASS_KEY,
                array('width' => -1, 'length' => -1, 'height' => -1, 'weight' => -1)
            );
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        $this->assertCount(4, $errors, 'All invalid fields must be validated.');
        foreach ($errors as $error) {
            $this->assertEquals(ValidationError::ERROR_INVALID_FIELD, $error->code);
        }

        $this->assertEquals('width', $errors[0]->field);
        $this->assertEquals('length', $errors[1]->field);
        $this->assertEquals('height', $errors[2]->field);
        $this->assertEquals('weight', $errors[3]->field);
    }
}
