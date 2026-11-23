<?php

namespace Logeecom\Tests\BusinessLogic\Http\DTO;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\PlanPrice;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;

/**
 * Class PricesPerPlanDtoTest. Covers the price simulation request parameter and the per-plan price parsing.
 *
 * @package Logeecom\Tests\BusinessLogic\Http\DTO
 */
class PricesPerPlanDtoTest extends BaseTestWithServices
{
    public function testSearchWithoutPriceSimulationIsUnchanged()
    {
        $search = new ShippingServiceSearch(null, 'ES', '28001', 'ES', '08001', array(Package::defaultPackage()));

        $data = $search->toArray();

        $this->assertArrayNotHasKey('price_simulation', $data);
    }

    public function testSearchWithPriceSimulationAddsParameterLast()
    {
        $search = new ShippingServiceSearch(null, 'ES', '28001', 'ES', '08001', array(Package::defaultPackage()));
        $search->priceSimulation = ShippingServiceSearch::PRICE_SIMULATION_ALL;

        $data = $search->toArray();
        $keys = array_keys($data);

        $this->assertEquals('all', $data['price_simulation']);
        $this->assertEquals('price_simulation', end($keys));
    }

    public function testNestedPricesPerPlanAreParsed()
    {
        $details = ShippingServiceDetails::fromArray($this->getNestedServiceResponse());

        $this->assertCount(3, $details->pricesPerPlan);
        $this->assertEquals('free_es', $details->currentPlanCode);
        $this->assertEquals('plus_es', $details->nextPlanCode);
        $plus = $this->findPlan($details->pricesPerPlan, 'plus_es');
        $this->assertEquals(5.45, $plus->totalPrice);
        $this->assertEquals(4.5, $plus->basePrice);
        $this->assertEquals(0.95, $plus->taxPrice);
        $this->assertEquals('EUR', $plus->currency);
    }

    public function testTopLevelPricesPerPlanAreParsedAsFallback()
    {
        $raw = array(
            'id' => 1,
            'prices_per_plan' => array(
                array('plan_code' => 'premium_es', 'price' => array('total_price' => 18.23, 'currency' => 'EUR')),
            ),
        );

        $details = ShippingServiceDetails::fromArray($raw);

        $this->assertCount(1, $details->pricesPerPlan);
        $this->assertEquals('premium_es', $details->pricesPerPlan[0]->planCode);
        $this->assertEquals(18.23, $details->pricesPerPlan[0]->totalPrice);
    }

    public function testMissingPricesPerPlanYieldsEmptyList()
    {
        $details = ShippingServiceDetails::fromArray(array('id' => 1, 'price' => array('total_price' => 4.1)));

        $this->assertSame(array(), $details->pricesPerPlan);
        $this->assertNull($details->currentPlanCode);
        $this->assertNull($details->nextPlanCode);
    }

    public function testNullPricesStayNull()
    {
        $raw = $this->getNestedServiceResponse();
        $raw['price'] = null;
        $raw['price_simulation']['prices_per_plan'][0]['price'] = null;

        $details = ShippingServiceDetails::fromArray($raw);

        $this->assertNull($details->totalPrice);
        $free = $this->findPlan($details->pricesPerPlan, 'free_es');
        $this->assertNull($free->totalPrice);
        $this->assertNull($free->currency);
    }

    public function testTierFromPlanCode()
    {
        $this->assertEquals('FREE', PlanPrice::tierFromPlanCode('free_es'));
        $this->assertEquals('PLUS', PlanPrice::tierFromPlanCode('plus_es'));
        $this->assertEquals('PREMIUM', PlanPrice::tierFromPlanCode('premium_es'));
        $this->assertEquals('PREMIUM', PlanPrice::tierFromPlanCode('premium_fr'));
        $this->assertEquals('PLUS', PlanPrice::tierFromPlanCode('plus'));
        $this->assertNull(PlanPrice::tierFromPlanCode('unknown_es'));
        $this->assertNull(PlanPrice::tierFromPlanCode(''));
        $this->assertNull(PlanPrice::tierFromPlanCode(null));
    }

    public function testTierOrder()
    {
        $this->assertEquals(array('FREE', 'PLUS', 'PREMIUM'), PlanPrice::getTiers());
    }

    /**
     * @param PlanPrice[] $plans
     * @param string $code
     *
     * @return PlanPrice
     */
    private function findPlan(array $plans, $code)
    {
        foreach ($plans as $plan) {
            if ($plan->planCode === $code) {
                return $plan;
            }
        }

        $this->fail("Plan $code not parsed.");

        return null;
    }

    /**
     * @return array
     */
    private function getNestedServiceResponse()
    {
        return array(
            'id' => 123,
            'name' => 'Example Standard',
            'carrier_name' => 'Example Carrier',
            'price' => array('total_price' => 6.05, 'base_price' => 5.00, 'tax_price' => 1.05, 'currency' => 'EUR'),
            'price_simulation' => array(
                'current_plan_code' => 'free_es',
                'worst_case_plan_code' => 'free_es',
                'next_case_plan_code' => 'plus_es',
                'prices_per_plan' => array(
                    array(
                        'plan_code' => 'free_es',
                        'price' => array('total_price' => 6.05, 'base_price' => 5.00, 'tax_price' => 1.05, 'currency' => 'EUR'),
                    ),
                    array(
                        'plan_code' => 'plus_es',
                        'price' => array('total_price' => 5.45, 'base_price' => 4.50, 'tax_price' => 0.95, 'currency' => 'EUR'),
                    ),
                    array(
                        'plan_code' => 'premium_es',
                        'price' => array('total_price' => 4.84, 'base_price' => 4.00, 'tax_price' => 0.84, 'currency' => 'EUR'),
                    ),
                ),
            ),
        );
    }
}
