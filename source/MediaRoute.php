<?php
namespace SeanMorris\MediaGate;

use Ethereum\EcRecover;

class MediaRoute implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		header('Content-type: text/json');
		header('Access-Control-Allow-Origin: *');

		$github = \SeanMorris\Ids\Settings::get('github', 'token');

		$file = $github->api('repo')->contents()->show(
			'seanmorris', 'ephsys-media-processor', 'index/content.json'
		);

		$blob = $github->api('gitData')->blobs()->show(
			'seanmorris', 'ephsys-media-processor'
			, substr($file['_links']['git'], -40)
		);

		return base64_decode($blob['content']);
	}

	public function show($router)
	{
		header('Access-Control-Allow-Headers:authorization, content-type, accept, origin');
		header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
		header('Access-Control-Allow-Origin: *');

		$token = $router->request()->headers('Authorization');

		if(!$token)
		{
			return FALSE;
		}

		if(substr($token, 0, 7) !== 'Bearer ')
		{
			return FALSE;
		}

		$token = json_decode(substr($token, 7));

		if(empty($token->time)
			|| empty($token->address)
			|| empty($token->signature)
			|| empty($token->challenge)
		){
			return FALSE;
		}

		$challenge = json_decode($token->challenge);

		if(!$challenge)
		{
			return FALSE;
		}

		if(time() > $challenge->validThru)
		{
			return FALSE;
		}

		$valid = EcRecover::personalVerifyEcRecover(
			$token->challenge
			, $token->signature
			, $token->address
		);

		$recoveredAddress = EcRecover::personalEcRecover(
			$token->challenge
			,  $token->signature
			,  $token->address
		);

		if(!$valid || $token->address !== $recoveredAddress)
		{
			return FALSE;
		}

		$headers = [
			'json'  => 'Content-type: application/json'
			, 'html' => 'Content-type: text/html'
			, 'jpg'  => 'Content-type: image/jpeg'
			, 'png'  => 'Content-type: image/png'
			, 'mp3'  => 'Content-type: audio/mpeg'
			, 'mp4'  => 'Content-type: video/mpeg'
		];

		$assetPath = $router->request()->get('assetPath') ?? '';

		$header = $headers[substr($assetPath, -3)] ?? $headers[substr($assetPath, -4)];

		if(!$header)
		{
			return FALSE;
		}

		header($header);

		$github = \SeanMorris\Ids\Settings::get('github', 'token');

		$assetPathParts = explode('/', $assetPath);

		array_pop($assetPathParts);

		$directoryPath = implode($assetPathParts);

		$files = $github->api('repo')->contents()
		->show('seanmorris', 'ephsys-media-processor', $directoryPath);

		foreach($files as $file)
		{
			if($file['path'] === $assetPath)
			{
				$blob = $github->api('gitData')->blobs()->show(
					'seanmorris', 'ephsys-media-processor'
					, substr($file['_links']['git'], -40)
				);

				return base64_decode($blob['content']);
				break;
			}
		}
	}
}
