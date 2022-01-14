<?php
namespace SeanMorris\MediaGate;

class VerifyRoute implements \SeanMorris\Ids\Routable
{
	public $routes = [
		'purchase' => \SeanMorris\MediaGate\PurchaseRoute::CLASS
		, 'media'  => \SeanMorris\MediaGate\MediaRoute::CLASS
		, 'jobs'   => \SeanMorris\MediaGate\JobsRoute::CLASS
	];

	public function index($router)
	{
		return false;

		$frontend  = \SeanMorris\Ids\Settings::read('frontend');
		$signature = $router->request()->post('signature');
		$address =   $router->request()->post('address');
		$message =   $router->request()->post('message');


		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: ' . $frontend);
		header('Access-Control-Allow-Credentials: true');

		if(!$address || !$message || !$signature)
		{
			return json_encode((object) ([
				'error' => 'Required: address, message and signature.'
				, 'requested' => (object) [
					'message'     => $message
					, 'address'   => $address
					, 'signature' => $signature
				]
				, 'response' => (object) ['valid' => FALSE]
			]));
		}

		$valid = EcRecover::personalVerifyEcRecover($message,  $signature,  $address);
		$recoveredAddress = EcRecover::personalEcRecover($message, $signature);

		return json_encode((object) ([
			'requested' => (object) [
				'message'     => $message
				, 'address'   => $address
				, 'signature' => $signature
			]
			, 'response' => (object) [
				'recoveredAddress' => $recoveredAddress
				, 'valid' => $valid
			]
		]));
	}

	public function challenge($router)
	{
		$frontend  = \SeanMorris\Ids\Settings::read('frontend');

		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: ' . $frontend);
		header('Access-Control-Allow-Credentials: true');

		$retort = $router->request()->post('retort');

		if($retort)
		{
			$retort = json_decode($retort);
			$result = AccessToken::validate($retort);

			return json_encode((object) ([
				'requested' => $retort
				, 'response' => (object) [
					'recoveredAddress' => $result->recoveredAddress
					, 'valid' => $result->valid
				]
			]));
		}

		return json_encode( AccessToken::generate( $router->request()->post('address') ) );
	}
}
