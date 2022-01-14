<?php
namespace SeanMorris\MediaGate;

class Subscription
{
	public static function assignExpiry($fromAddress)
	{
		$streamName = 'TX-'. $fromAddress;
		$price = \SeanMorris\Ids\Settings::read('sub', 'price');

		$rates = Exchanger::getLatestRates();
		$ethToUsd = new Currency($rates->ETH);
		$usdPrice = new Currency($price);

		$ethPrice = $ethToUsd->amount * ($usdPrice->amount + -1);

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

		return $results;
	}
}
