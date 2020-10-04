<?php

namespace tests;

use CbrDayRateSoap\DayRateGetter;
use DateTime;
use DayRate\Currency;
use PHPUnit\Framework\TestCase;
use CbrSimpleSoap\Client;

/**
 * Class DayRateGetter
 *
 * @package CbrDayRateSoap
 */
class DayRateGetterTest extends TestCase
{
    /**
     * @param $cbrSoapClient
     * @param Currency $quoteCurrency
     * @param Currency $baseCurrency
     * @param float $price
     *
     * @dataProvider getSuccessData
     */
    public function testSuccess($cbrSoapClient, Currency $quoteCurrency, Currency $baseCurrency, float $price)
    {
        $dayRateGetter = new DayRateGetter($cbrSoapClient);
        $dayRate = $dayRateGetter->get(
            new DateTime(),
            $quoteCurrency,
            $baseCurrency
        );
        $this->assertEquals($quoteCurrency->getCode(), $dayRate->getQuoteCurrency()->getCode());
        $this->assertEquals($baseCurrency->getCode(), $dayRate->getBaseCurrency()->getCode());
        $this->assertEquals($price, $dayRate->getPrice());
    }

    /**
     * @return array[]
     */
    public function getSuccessData()
    {
        return [
            (function () {
                $rate = [];
                $rate['Vname'] = 'name';
                $rate['Vnom'] = '1';
                $rate['Vcurs'] = '70.0';
                $rate['Vcode'] = '36';
                $rate['VchCode'] = 'USD';
                $price = (float)$rate['Vcurs'];
                $result = [$rate];
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('USD');
                $baseCurrency = new Currency('RUR');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency,
                    $price
                ];
            })(),
            (function () {
                $rate = [];
                $rate['Vname'] = 'name';
                $rate['Vnom'] = '1';
                $rate['Vcurs'] = '70.0';
                $rate['Vcode'] = '36';
                $rate['VchCode'] = 'USD';
                $price = 1 / (float)$rate['Vcurs'];
                $result = [$rate];
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('RUR');
                $baseCurrency = new Currency('USD');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency,
                    $price
                ];
            })(),
            (function () {
                $rate = [];
                $rate['Vname'] = 'name';
                $rate['Vnom'] = '1';
                $rate['Vcurs'] = '70.0';
                $rate['Vcode'] = '1';
                $rate['VchCode'] = 'USD';
                $result = [
                    [
                        'Vname' => 'name',
                        'Vnom' => '1',
                        'Vcurs' => '70.0',
                        'Vcode' => '1',
                        'VchCode' => 'USD',
                    ],
                    [
                        'Vname' => 'name',
                        'Vnom' => '1',
                        'Vcurs' => '7.0',
                        'Vcode' => '1',
                        'VchCode' => 'EUR',
                    ],
                ];
                $price = 10;
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('USD');
                $baseCurrency = new Currency('EUR');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency,
                    $price
                ];
            })(),
            (function () {
                $price = 1;
                $result = [];
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('RUR');
                $baseCurrency = new Currency('RUR');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency,
                    $price
                ];
            })(),
        ];
    }

    /**
     * @param $cbrSoapClient
     * @param Currency $quoteCurrency
     * @param Currency $baseCurrency
     *
     * @dataProvider getEmptyData
     */
    public function testEmpty($cbrSoapClient, Currency $quoteCurrency, Currency $baseCurrency)
    {
        $dayRateGetter = new DayRateGetter($cbrSoapClient);
        $dayRate = $dayRateGetter->get(
            new DateTime(),
            $quoteCurrency,
            $baseCurrency
        );
        $this->assertNull($dayRate);
    }

    /**
     * @return array[]
     */
    public function getEmptyData()
    {
        return [
            (function () {
                $result = [];
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('USD');
                $baseCurrency = new Currency('RUR');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency
                ];
            })(),
            (function () {
                $rate = [];
                $rate['Vname'] = 'name';
                $rate['Vnom'] = '1';
                $rate['Vcurs'] = '70.0';
                $rate['Vcode'] = '36';
                $rate['VchCode'] = 'USD';
                $price = (float)$rate['Vcurs'];
                $result = [$rate];
                $cbrSoapClient = $this->createMock(Client::class);
                $cbrSoapClient->method('GetCursOnDateXML')->willReturn($result);
                $quoteCurrency = new Currency('EUR');
                $baseCurrency = new Currency('RUR');
                return [
                    $cbrSoapClient,
                    $quoteCurrency,
                    $baseCurrency,
                    $price
                ];
            })(),
        ];
    }
}
