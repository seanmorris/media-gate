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

		$streamName = 'TX-'. $fromAddress;
		$price = 'USD1'; //\SeanMorris\Ids\Settings::read('sub', 'price');

		$rates = Exchanger::getLatestRates();
		$ethToUsd = new Currency($rates->ETH);
		$usdPrice = new Currency('USD5');

		$ethPrice = $ethToUsd->amount * $usdPrice->amount;

		$redis = \SeanMorris\Ids\Settings::get('redis');

		$transactions = $redis->xRange($streamName, '-', '+');

		$maxAge = 60 * 60 * 24 * 30;

		$results = [];

		foreach($transactions as $transaction)
		{
			$transaction = (object) $transaction;

			$txValue = $transaction->value / 1000000000000000000;
			$age = time() - $transaction->timeStamp;

			// Skip transactions older than a month
			if($age > $maxAge)
			{
				continue;
			}

			// Skip transactions that are below the price
			if($txValue < $ethPrice)
			{
				continue;
			}

			$subKey = 'SUBS-' . $transaction->from;

			if($existingSubscription = $redis->get($subKey))
			{
				$existingSubscription = json_decode($existingSubscription);
			}

			// Create new subscriptions if one isn't found
			if(!$existingSubscription)
			{
				$subscription = (object) [
					'latestTransaction' => $transaction
					, 'ethUsdRate'      => $ethToUsd
					, 'currentPrice'    => $usdPrice
					, 'expiry'          => time() + 60 * 60 * 24 * 30
				];

				$redis->set($subKey, json_encode($subscription));

				$results[$transaction->from] = $subscription->expiry;

				continue;
			}

			$latestTranaction = $existingSubscription->latestTransaction;

			// Skip transactions older than the latest one on the account
			if($latestTranaction->timeStamp >= $transaction->timeStamp)
			{
				$results[$latestTranaction->from] = $existingSubscription->expiry;

				continue;
			}

			// Add a month onto the existing subscription
			$subscription = (object) [
				'latestTransaction' => $transaction
				, 'ethUsdRate'      => $ethToUsd
				, 'currentPrice'    => $usdPrice
				, 'expiry'          => $existingSubscription->expiry + 60 * 60 * 24 * 30
			];

			$redis->set($subKey, json_encode($subscription));

			$results[$transaction->from] = $subscription->expiry;
		}

		return json_encode($results);
	}
}
