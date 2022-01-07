<?php
namespace SeanMorris\MediaGate;

use Ethereum\EcRecover;

class VerifyRoute implements \SeanMorris\Ids\Routable
{
	public $routes = [
		'media' => \SeanMorris\MediaGate\MediaRoute::CLASS
	];

	public function index($router)
	{
		return false;

		$signature = $router->request()->post('signature');
		$address =   $router->request()->post('address');
		$message =   $router->request()->post('message');

		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: *');

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
		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: *');

		$retort  = $router->request()->post('retort');

		if($retort)
		{
			$retort = json_decode($retort);

			if(empty($retort->time)
				|| empty($retort->address)
				|| empty($retort->signature)
				|| empty($retort->challenge)
			){
				return json_encode((object) ([
					'error'       => ''
					, 'requested' => ''
					, 'response'  => (object) ['valid' => FALSE]
				]));
			}

			$valid = EcRecover::personalVerifyEcRecover(
				$retort->challenge,  $retort->signature, $retort->address
			);

			$recoveredAddress = EcRecover::personalEcRecover(
				$retort->challenge,  $retort->signature,  $retort->address
			);

			return json_encode((object) ([
				'requested' => $retort
				, 'response' => (object) [
					'recoveredAddress' => $recoveredAddress
					, 'valid' => $valid
				]
			]));
		}

		$address = $router->request()->post('address');
		$blob = bin2hex(openssl_random_pseudo_bytes(64));

		return json_encode([
			'type'        => 'challenge'
			, 'issuedAt'  => time()
			, 'issuedFor' => $address
			, 'validThru' => time() + 150
			, 'challenge' => implode(PHP_EOL, str_split($blob, 80))
		]);
	}
}
