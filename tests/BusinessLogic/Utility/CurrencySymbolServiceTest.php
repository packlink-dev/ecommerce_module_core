<?php


namespace BusinessLogic\Utility;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Utility\CurrencySymbolService;

class CurrencySymbolServiceTest extends BaseTestWithServices
{
    public function testValidCurrency()
    {
        $this->assertEquals('€', CurrencySymbolService::getCurrencySymbol('EUR'));
        $this->assertEquals('$', CurrencySymbolService::getCurrencySymbol('USD'));
        $this->assertEquals('£', CurrencySymbolService::getCurrencySymbol('GBP'));
    }

    public function testInvalidCurrency()
    {
        $this->assertEquals('test', CurrencySymbolService::getCurrencySymbol('test'));
        $this->assertEquals('zzz', CurrencySymbolService::getCurrencySymbol('zzz'));
        $this->assertEquals('', CurrencySymbolService::getCurrencySymbol(''));
    }
}
