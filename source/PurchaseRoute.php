<?php
namespace SeanMorris\MediaGate;

class PurchaseRoute implements \SeanMorris\Ids\Routable
{
	public function price($router)
	{
		$rates = Exchanger::getLatestRates();
		$ethToUsd = new Currency($rates->ETH);
		$usdPrice = new Currency('USD5');
		$ethPrice = $ethToUsd->amount * $usdPrice->amount;

		$frontend  = \SeanMorris\Ids\Settings::read('frontend');

		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: ' . $frontend);
		header('Access-Control-Allow-Credentials: true');

		return json_encode([
			'ETH' => $ethPrice, 'USD' => (int) $usdPrice->amount
		]);
	}

	public function status($router)
	{
		$frontend  = \SeanMorris\Ids\Settings::read('frontend');

		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: ' . $frontend);
		header('Access-Control-Allow-Credentials: true');

		if(!$fromAddress = $router->path()->consumeNode())
		{
			return FALSE;
		}

		$subKey = 'SUBS-' . $fromAddress;

		$redis = \SeanMorris\Ids\Settings::get('redis');

		if(!$existingSubscription = json_decode($redis->get($subKey)))
		{
			return FALSE;
		}

		if($existingSubscription->expiry < time())
		{
			return FALSE;
		}

		return json_encode($existingSubscription);
	}
}
