<?php
/** @noinspection PhpDocMissingThrowsInspection */

namespace Logeecom\Tests\BusinessLogic\Dto;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;

/**
 * Class ShippingPricePolicyDtoTest.
 *
 * @package BusinessLogic\Dto
 */
class ShippingPricePolicyDtoTest extends BaseDtoTest
{
    protected function setUp()
    {
        parent::setUp();

        TestFrontDtoFactory::register(ShippingPricePolicy::CLASS_KEY, ShippingPricePolicy::CLASS_NAME);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testFromArray()
    {
        /** @var ShippingPricePolicy[] $policies */
        $policies = TestFrontDtoFactory::getFromBatch(ShippingPricePolicy::CLASS_KEY, $this->getValidPolicies());
        $this->assertCount(6, $policies);
        $this->assertEquals(ShippingPricePolicy::RANGE_PRICE, $policies[0]->rangeType);
        $this->assertEquals(ShippingPricePolicy::RANGE_WEIGHT, $policies[1]->rangeType);

        // validate all fields
        $this->assertNull($policies[0]->fromWeight);
        $this->assertNull($policies[0]->toWeight);
        $this->assertNull($policies[0]->changePercent);
        $this->assertNull($policies[0]->fixedPrice);
        $this->assertNull($policies[1]->fromPrice);
        $this->assertNull($policies[1]->toPrice);
        $this->assertEquals(ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT, $policies[2]->rangeType);
        $this->assertEquals(0.5, $policies[2]->fromPrice);
        $this->assertEquals(20.98, $policies[2]->toPrice);
        $this->assertEquals(0.05, $policies[2]->fromWeight);
        $this->assertEquals(0.6, $policies[2]->toWeight);
        $this->assertEquals(ShippingPricePolicy::POLICY_PACKLINK_ADJUST, $policies[2]->pricingPolicy);
        $this->assertTrue($policies[2]->increase);
        $this->assertEquals(54.248, $policies[2]->changePercent);
    }

    public function testToArray()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $policies = TestFrontDtoFactory::getFromBatch(ShippingPricePolicy::CLASS_KEY, $this->getValidPolicies());
        $policy = $policies[2];

        $array = $policy->toArray();
        $this->assertEquals(ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT, $array['range_type']);
        $this->assertEquals(0.5, $array['from_price']);
        $this->assertEquals(20.98, $array['to_price']);
        $this->assertEquals(0.05, $array['from_weight']);
        $this->assertEquals(0.6, $array['to_weight']);
        $this->assertEquals(ShippingPricePolicy::POLICY_PACKLINK_ADJUST, $array['pricing_policy']);
        $this->assertTrue($array['increase']);
        $this->assertEquals(54.25, $array['change_percent']);

        // assert empty values
        $array = $policies[0]->toArray();
        $this->assertNull($array['from_weight']);
        $this->assertNull($array['to_weight']);
        $this->assertNull($array['change_percent']);
        $this->assertNull($array['fixed_price']);

        $array = $policies[1]->toArray();
        $this->assertNull($array['from_price']);
        $this->assertNull($array['to_price']);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function testBaseRequiredFieldsValidation()
    {
        $errors = null;
        try {
            TestFrontDtoFactory::get(ShippingPricePolicy::CLASS_KEY, array());
        } catch (FrontDtoValidationException $e) {
            $errors = $e->getValidationErrors();
        }

        $this->assertCount(2, $errors, 'Price Range and Pricing Policy fields must be validated.');
        foreach ($errors as $error) {
            $this->assertEquals(ValidationError::ERROR_REQUIRED_FIELD, $error->code);
        }
    }

    public function testPriceRangeValidation()
    {
        $policy = array(
            'range_type' => ShippingPricePolicy::RANGE_PRICE,
            'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
        );

        $this->assertInvalidField(
            $policy,
            'from_price',
            'From price is required for price range.',
            ValidationError::ERROR_REQUIRED_FIELD
        );

        $policy['from_price'] = -1;
        $this->assertInvalidField($policy, 'from_price', 'Negative range should be validated.');

        $policy['from_price'] = 10;
        $this->assertNull($this->getErrors($policy), 'Pricing policy is valid without upper bound.');

        $policy['to_price'] = -1;
        $this->assertInvalidField($policy, 'to_price', 'Negative range should be validated.');

        $policy['to_price'] = 5;
        $this->assertInvalidField($policy, 'to_price', 'Upper boundary must be higher than the lower boundary.');

        $policy['to_price'] = $policy['from_price'];
        $this->assertInvalidField($policy, 'to_price', 'Upper boundary must be higher than the lower boundary.');

        $policy['to_price'] = $policy['from_price'] + 1;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');
    }

    public function testWeightRangeValidation()
    {
        $policy = array(
            'range_type' => ShippingPricePolicy::RANGE_WEIGHT,
            'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
        );

        $this->assertInvalidField(
            $policy,
            'from_weight',
            'From price is required for price range.',
            ValidationError::ERROR_REQUIRED_FIELD
        );

        $policy['from_weight'] = -1;
        $this->assertInvalidField($policy, 'from_weight', 'Negative range should be validated.');

        $policy['from_weight'] = 10;
        $this->assertNull($this->getErrors($policy), 'Pricing policy is valid without upper bound.');

        $policy['to_weight'] = -1;
        $this->assertInvalidField($policy, 'to_weight', 'Negative range should be validated.');

        $policy['to_weight'] = 5;
        $this->assertInvalidField($policy, 'to_weight', 'Upper boundary must be higher than the lower boundary.');

        $policy['to_weight'] = $policy['from_weight'];
        $this->assertInvalidField($policy, 'to_weight', 'Upper boundary must be higher than the lower boundary.');

        $policy['to_weight'] = $policy['from_weight'] + 1;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');
    }

    public function testPriceAndWeightRangeValidation()
    {
        $policy = array(
            'range_type' => ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT,
            'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
        );

        $this->assertCount(2, $this->getErrors($policy), 'Both from_price and from_weight are required');

        $policy['from_weight'] = -1;
        $policy['from_price'] = -1;
        $this->assertCount(2, $this->getErrors($policy), 'Both from_price and from_weight must be positive');

        $policy['from_price'] = 0;
        $this->assertInvalidField($policy, 'from_weight', 'Negative range should be validated.');

        $policy['from_price'] = -10;
        $policy['from_weight'] = 0;
        $this->assertInvalidField($policy, 'from_price', 'Negative range should be validated.');

        $policy['from_weight'] = $policy['from_price'] = 10;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');

        $policy['to_weight'] = -1;
        $this->assertInvalidField($policy, 'to_weight', 'Negative range should be validated.');
        $policy['to_weight'] = 5;
        $this->assertInvalidField($policy, 'to_weight', 'Upper boundary must be higher than the lower boundary.');
        $policy['to_weight'] = $policy['from_weight'];
        $this->assertInvalidField($policy, 'to_weight', 'Upper boundary must be higher than the lower boundary.');
        $policy['to_weight'] = $policy['from_weight'] + 1;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');

        unset($policy['to_weight']);

        $policy['to_price'] = -1;
        $this->assertInvalidField($policy, 'to_price', 'Negative range should be validated.');
        $policy['to_price'] = 5;
        $this->assertInvalidField($policy, 'to_price', 'Upper boundary must be higher than the lower boundary.');
        $policy['to_price'] = $policy['from_price'];
        $this->assertInvalidField($policy, 'to_price', 'Upper boundary must be higher than the lower boundary.');
        $policy['to_price'] = $policy['from_price'] + 1;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');

        $policy['to_weight'] = $policy['from_weight'] + 1;
        $this->assertNull($this->getErrors($policy), 'Pricing policy should be valid.');
    }

    public function testFixedPricePolicy()
    {
        $policy = array(
            'range_type' => ShippingPricePolicy::RANGE_PRICE,
            'from_price' => 0,
            'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
        );

        $this->assertInvalidField(
            $policy,
            'fixed_price',
            'Fixed price is required for price range.',
            ValidationError::ERROR_REQUIRED_FIELD
        );

        $policy['fixed_price'] = -1;
        $this->assertInvalidField($policy, 'fixed_price', 'Negative range should be validated.');

        $policy['fixed_price'] = 0;
        $this->assertNull($this->getErrors($policy), 'Fixed price can be 0');

        $policy['fixed_price'] = 10;
        $this->assertNull($this->getErrors($policy), 'Valid float should be accepted');
    }

    private function assertInvalidField($policy, $field, $error, $errorType = ValidationError::ERROR_INVALID_FIELD)
    {
        $errors = $this->getErrors($policy);
        $this->assertCount(1, $errors, $error);
        $this->assertEquals($errorType, $errors[0]->code);
        $this->assertEquals($field, $errors[0]->field);
    }

    private function getValidPolicies()
    {
        return array(
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
            ),
            array(
                'range_type' => ShippingPricePolicy::RANGE_WEIGHT,
                'from_weight' => 34,
                'to_weight' => 45,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK,
            ),
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT,
                'from_price' => 0.5,
                'to_price' => 20.98,
                'from_weight' => 0.05,
                'to_weight' => 0.6,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK_ADJUST,
                'increase' => true,
                'change_percent' => 54.248,
            ),
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_FIXED_PRICE,
                'fixed_price' => 43.98,
            ),
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0,
                'to_price' => 20,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK_ADJUST,
                'increase' => true,
                'change_percent' => 35,
            ),
            array(
                'range_type' => ShippingPricePolicy::RANGE_PRICE,
                'from_price' => 0.5,
                'to_price' => 20.76,
                'pricing_policy' => ShippingPricePolicy::POLICY_PACKLINK_ADJUST,
                'increase' => false,
                'change_percent' => 56,
            ),
        );
    }

    /**
     * @param array $policy
     *
     * @return array
     */
    private function getErrors(array $policy)
    {
        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            TestFrontDtoFactory::get(ShippingPricePolicy::CLASS_KEY, $policy);
        } catch (FrontDtoValidationException $e) {
            return $e->getValidationErrors();
        }

        return null;
    }
}
