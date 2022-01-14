<?php
namespace SeanMorris\MediaGate;

class Exchanger
{
	public static function captureExchangeRates($currency = 'USD')
	{
		$http = new Http;

		$streamName = 'EXG-' . $currency;

		$response = $http->httpRequest(
			'https://api.coinbase.com/v2/exchange-rates?currency=' . $currency
		);

		$response = json_decode($response);

		$currency = $response->data->currency;
		$rates    = (array) $response->data->rates;

		$rates = array_map(
			function ($rate) use($currency){return $currency . $rate;}
			, $rates
		);

		ksort($rates);

		$redis = \SeanMorris\Ids\Settings::get('redis');

		$success = $redis->xadd(
			$streamName
			, '*'
			, $rates
		);

		return $success;
	}

	public static function getLatestRates()
	{
		$redis = \SeanMorris\Ids\Settings::get('redis');

		$latestRates = $redis->xRevRange('EXG-USD', '+', '-', '1');

		if(!$latestRates)
		{
			static::captureExchangeRates();
			$latestRates = $redis->xRevRange('EXG-USD', '+', '-', '1');
		}

		$sequenceCode = array_keys($latestRates)[0];

		[$time, $seq] = explode('-', $sequenceCode);

		return (object) $latestRates[$sequenceCode];
	}
}
