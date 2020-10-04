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
    public function get(DateTime $dateTime, Currency $quoteCurrency, Currency $baseCurrency): ?DayRate
    {
        $returnDayRate = null;
        $responseDayRateList = [];
        $responseDateTime = clone $dateTime;
        $responseDateTime->setTime(0, 0, 0, 0);
        if ($quoteCurrency->getCode() === $baseCurrency->getCode()) {
            $returnDayRate = new DayRate(
                $quoteCurrency,
                $baseCurrency,
                $responseDateTime,
                1.0
            );

            return $returnDayRate;
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

        $returnDayRate = $this->findSimpleOrReversed($responseDayRateList, $quoteCurrency, $baseCurrency);

        if ($returnDayRate === null) {
            /**
             * @note Code below tries to find rate across other rates
             * @warning Searching tries to find across RUR as base currency only,
             * cause CBR has only RUR as base currency
             */
            if ($defaultBaseCurrency->getCode() !== $baseCurrency->getCode()) {
                $firstDayRate = $this->findSimpleOrReversed($responseDayRateList, $quoteCurrency, $defaultBaseCurrency);
                $secondDayRate = $this->findSimpleOrReversed($responseDayRateList, $defaultBaseCurrency, $baseCurrency);
                if ($firstDayRate !== null && $secondDayRate !== null) {
                    $returnDayRate = new DayRate(
                        $quoteCurrency,
                        $baseCurrency,
                        $dateTime,
                        $firstDayRate->getPrice() * $secondDayRate->getPrice()
                    );
                }
            }
        }

        return $returnDayRate;
    }

    /**
     * @param array $responseDayRateList
     * @param Currency $quoteCurrency
     * @param Currency $baseCurrency
     *
     * @return DayRate|null
     */
    private function findSimpleOrReversed(
        array $responseDayRateList,
        Currency $quoteCurrency,
        Currency $baseCurrency
    ): ?DayRate {
        $returnDayRate = null;
        foreach ($responseDayRateList as $dayRate) {
            if (
                $quoteCurrency->getCode() === $dayRate->getQuoteCurrency()->getCode()
                && $baseCurrency->getCode() === $dayRate->getBaseCurrency()->getCode()
            ) {
                $returnDayRate = $dayRate;
                break;
            } elseif (
                $baseCurrency->getCode() === $dayRate->getQuoteCurrency()->getCode()
                && $quoteCurrency->getCode() === $dayRate->getBaseCurrency()->getCode()
            ) {
                $newDayRate = clone $dayRate;
                $newDayRate->setQuoteCurrency($dayRate->getBaseCurrency());
                $newDayRate->setBaseCurrency($dayRate->getQuoteCurrency());
                $newDayRate->setPrice(1 / $dayRate->getPrice());
                $returnDayRate = $newDayRate;
                break;
            }
        }
        return $returnDayRate;
    }
}
