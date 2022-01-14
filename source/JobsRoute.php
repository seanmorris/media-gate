<?php
namespace SeanMorris\MediaGate;

class JobsRoute implements \SeanMorris\Ids\Routable
{
	// Load incoming transactions to redis
	public function loadTransactions($router)
	{
		$transactions = Transaction::loadIncoming();

		$results = [];

		foreach($transactions->result as $transaction)
		{
			$maxAge = 60 * 60 * 24 * 30;
			$age = time() - $transaction->timeStamp;

			if($age > $maxAge)
			{
				continue;
			}

			$ttl = $maxAge + -$age;

			$redis = \SeanMorris\Ids\Settings::get('redis');

			$fromAddress = $transaction->from;
			$streamName  = 'TX-' . $fromAddress;
			$msTimeStamp = $transaction->timeStamp * 1000;

			$success = $redis->xadd(
				$streamName
				, $msTimeStamp
				, (array) $transaction
			);

			if(!isset($results[$fromAddress]))
			{
				$results[$fromAddress] = [
					'imported' => 0
					, 'exists' => 0
				];
			}

			if($success)
			{
				$results[$fromAddress]['imported']++;
			}
			else
			{
				$results[$fromAddress]['exists']++;
			}

			Subscription::assignExpiry($fromAddress);
		}

		return json_encode($results, 0, 4);
	}

	// Get the current set of exhange rates
	public function getExchangeRates($router)
	{
		return json_encode(Exchanger::getLatestRates());
	}

	// Log current exchange rates to redis
	public function captureExchangeRates($router)
	{
		return json_encode(Exchanger::captureExchangeRates());
	}

	// Update the expiry dates of user subscriptions
	public function assignExpiry($router)
	{
		if(!$fromAddress = $router->path()->consumeNode())
		{
			return FALSE;
		}

		$results = Subscription::assignExpiry($fromAddress);

		return json_encode($results);
	}
}
