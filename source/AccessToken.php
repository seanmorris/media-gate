<?php
namespace SeanMorris\MediaGate;

use Ethereum\EcRecover;

class AccessToken
{
	public static function generate($address)
	{
		$blob      = bin2hex(openssl_random_pseudo_bytes(64));
		$challenge = implode(PHP_EOL, str_split($blob, 80));

		$_SESSION['ether-challenge'] = $challenge;

		return (object) [
			'type'        => 'challenge'
			, 'issuedAt'  => time()
			, 'issuedFor' => $address
			, 'validThru' => time() + 10
			, 'challenge' => $challenge
		];
	}

	public static function validate($retort)
	{
		if(empty($retort->time)
			|| empty($retort->address)
			|| empty($retort->signature)
			|| empty($retort->challenge)
		){
			return (object) ([
				'error'       => 'Time, address, signature and challenge required.'
				, 'requested' => null
				, 'response'  => (object) ['valid' => FALSE]
			]);
		}

		$original = json_decode($retort->challenge);

		if(empty($original) || empty($original->challenge))
		{
			return (object) ([
				'error'       => 'Challenge invalid.'
				, 'requested' => null
				, 'response'  => (object) ['valid' => FALSE]
			]);
		}

		if(empty($_SESSION['ether-challenge']) || $original->challenge !== $_SESSION['ether-challenge'])
		{
			return (object) ([
				'error'       => 'Challenge invalid.'
				, 'requested' => [$retort->challenge, $_SESSION['ether-challenge']]
				, 'response'  => (object) ['valid' => FALSE]
			]);
		}

		if(time() > $original->validThru)
		{
			\SeanMorris\Ids\Log::debug('Challenge expired.');
			return FALSE;
		}

		$valid = EcRecover::personalVerifyEcRecover(
			$retort->challenge,  $retort->signature, $retort->address
		);

		$recoveredAddress = EcRecover::personalEcRecover(
			$retort->challenge,  $retort->signature,  $retort->address
		);

		return (object) ['valid' => $valid, 'recoveredAddress' => $recoveredAddress];
	}
}
