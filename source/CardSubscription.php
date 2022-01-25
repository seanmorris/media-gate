<?php
namespace SeanMorris\MediaGate;

class CardSubscription
{
	public static function assignExpiry($fromAddress, $transaction)
	{
		$price    = \SeanMorris\Ids\Settings::read('sub', 'price');
		$usdPrice = new Currency($price);
		$subKey   = 'SUBS-' . $fromAddress;
		$redis    = \SeanMorris\Ids\Settings::get('redis');

		$subscription = (object) [
			'latestTransaction' => $transaction
			, 'currentPrice'    => $usdPrice
			, 'expiry'          => time() + 60 * 60 * 24 * 30
		];

		$redis->set($subKey, json_encode($subscription));

		$results[$fromAddress] = $subscription->expiry;

		return $results;
	}
}
