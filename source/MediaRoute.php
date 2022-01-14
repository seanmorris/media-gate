<?php
namespace SeanMorris\MediaGate;

use Ethereum\EcRecover;

class MediaRoute implements \SeanMorris\Ids\Routable
{
	public function index()
	{
		$frontend  = \SeanMorris\Ids\Settings::read('frontend');

		header('Access-Control-Allow-Headers:authorization, content-type, accept, origin');
		header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: ' . $frontend);

		header('Content-type: text/json');

		$token  = \SeanMorris\Ids\Settings::read('github', 'token');
		$github = new Github($token);

		$repo = 'seanmorris/ephsys-media-processor';
		$file = 'index/content.json';

		return $github->getFile($repo, $file);
	}

	public function show($router)
	{
		$frontend  = \SeanMorris\Ids\Settings::read('frontend');

		header('Access-Control-Allow-Headers:authorization, content-type, accept, origin');
		header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: ' . $frontend);

		$token = $router->request()->headers('Authorization');

		if(!$token)
		{
			\SeanMorris\Ids\Log::debug('Access token not provided.');

			return FALSE;
		}

		if(substr($token, 0, 7) !== 'Bearer ')
		{
			\SeanMorris\Ids\Log::debug('Authorization header not valid.');

			return FALSE;
		}

		$token = json_decode(substr($token, 7));

		if(empty($token->time)
			|| empty($token->address)
			|| empty($token->signature)
			|| empty($token->challenge)
		){
			\SeanMorris\Ids\Log::debug('Access token not valid.');

			return FALSE;
		}

		$result = AccessToken::validate($token);

		$recoveredAddress = $result->recoveredAddress;
		$valid            = $result->valid;

		if(!$valid || $token->address !== $recoveredAddress)
		{
			\SeanMorris\Ids\Log::debug('Challenge address does not match.');
			return FALSE;
		}

		$subKey = 'SUBS-' . $recoveredAddress;

		$redis = \SeanMorris\Ids\Settings::get('redis');

		if(!$existingSubscription = json_decode($redis->get($subKey)))
		{
			return FALSE;
		}

		$headers = [
			'json'  => 'Content-type: application/json'
			, 'pdf'  => 'Content-type: application/pdf'
			, 'html' => 'Content-type: text/html'
			, 'jpg'  => 'Content-type: image/jpeg'
			, 'png'  => 'Content-type: image/png'
			, 'mp3'  => 'Content-type: audio/mpeg'
			, 'mp4'  => 'Content-type: video/mpeg'
		];

		$assetPath = $router->request()->get('assetPath') ?? '';

		$header = $headers[substr($assetPath, -3)]
			?? $headers[substr($assetPath, -4)];

		if(!$header)
		{
			\SeanMorris\Ids\Log::debug('Type not specified.');
			return FALSE;
		}

		header($header);

		$token  = \SeanMorris\Ids\Settings::read('github', 'token');
		$github = new Github($token);

		$assetPathParts = explode('/', $assetPath);

		array_pop($assetPathParts);

		$directoryPath = implode($assetPathParts);

		$repo = 'seanmorris/ephsys-media-processor';

		$files = $github->getDir($repo, $directoryPath);

		foreach($files as $file)
		{
			if($file->path === $assetPath)
			{
				$hash = substr($file->_links->git, -40);
				$blob = $github->getBlob($repo, $hash);

				header('Content-Length: ' . strlen($blob));

				ob_flush();
				ob_end_flush();

				while($blob)
				{
					print substr($blob, 0, 1024 * 1024);
					$blob = substr($blob, 1024 * 1024);
					usleep(1);
				}
				die;
			}
		}
	}
}
