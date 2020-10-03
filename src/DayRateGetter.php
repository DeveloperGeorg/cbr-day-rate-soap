<?php

namespace CbrDayRateSoap;

use CbrSimpleSoap\Client;
use DateTime;
use DayRate\Currency;
use DayRate\DayRate;
use DayRate\DayRateGetterInterface;
use SimpleXMLElement;

/**
 * Class DayRateGetter
 *
 * @package CbrDayRateSoap
 */
class DayRateGetter implements DayRateGetterInterface
{
    /**
     * @var Client
     */
    protected $cbrSoapClient;
    private const DEFAULT_BASE_CURRENCY = 'RUR';

    /**
     * DayRateGetter constructor.
     *
     * @param Client $cbrSoapClient
     */
    public function __construct(Client $cbrSoapClient)
    {
        $this->cbrSoapClient = $cbrSoapClient;
    }

    /**
     * @inheritDoc
     */
    public function getList(DateTime $dateTime, Currency $quoteCurrency, ?Currency $baseCurrency = null): array
    {
        $responseDayRateList = [];
        $responseDateTime = clone $dateTime;
        $responseDateTime->setTime(0, 0, 0, 0);
        if ($quoteCurrency->getCode() === $baseCurrency->getCode()) {
            $responseDayRateList[] = new DayRate(
                $quoteCurrency,
                $baseCurrency,
                $responseDateTime,
                1.0
            );

            return $responseDayRateList;
        }
        $defaultBaseCurrency = new Currency(static::DEFAULT_BASE_CURRENCY);
        $result = $this->cbrSoapClient->GetCursOnDateXML($dateTime);
        /** if any rate exists */
        if ($result->GetCursOnDateXMLResult->any) {
            $xml = new SimpleXMLElement($result->GetCursOnDateXMLResult->any);
            foreach ($xml->ValuteCursOnDate as $currency) {
                $rate = (float)$currency->Vcurs / (float)$currency->Vnom;
                if ($rate != 0) {
                    $responseDayRateList[] = new DayRate(
                        new Currency((string)$currency->VchCode),
                        $defaultBaseCurrency,
                        $responseDateTime,
                        $rate
                    );
                }
            }
        }

        if ($baseCurrency->getCode() !== $defaultBaseCurrency->getCode()) {
            /** @todo search */
            $dayRateList = $responseDayRateList;
        } else {
            $dayRateList = $responseDayRateList;
        }
        return $dayRateList;
    }
}
