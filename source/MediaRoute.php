<?php
namespace SeanMorris\MediaGate;

use Ethereum\EcRecover;

class MediaRoute implements \SeanMorris\Ids\Routable
{
	public function _init()
	{
		$frontend = \SeanMorris\Ids\Settings::read('frontend');

		header('Access-Control-Allow-Headers:authorization, content-type, accept, origin');
		header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Origin: ' . $frontend);
	}

	public function index()
	{
		$github = new Github(\SeanMorris\Ids\Settings::read('github', 'token'));
		$repo   = 'seanmorris/ephsys-media-processor';
		$list   = $github->getFile($repo, 'index/content.json');

		header('Content-type: text/json');
		return $list;
	}

	public function show($router)
	{
		$assetPath = $router->request()->get('assetPath') ?? '';
		$method    = $router->request()->method();
		$redis     = \SeanMorris\Ids\Settings::get('redis');

		if(!$mimeHeader = Mime::getType($assetPath))
		{
			\SeanMorris\Ids\Log::error('Type not specified.');
			return FALSE;
		}

		if($method === 'OPTIONS')
		{
			header('Content-Type: ' . $mimeHeader);
			return;
		}

		if(!$token = $router->request()->headers('Authorization'))
		{
			\SeanMorris\Ids\Log::debug('Access token not provided.');

			return FALSE;
		}

		if(substr($token, 0, 7) !== 'Bearer ')
		{
			\SeanMorris\Ids\Log::debug('Authorization header not valid.');

			return FALSE;
		}

		$token  = json_decode(substr($token, 7));
		$result = AccessToken::validate($token);

		$recoveredAddress = $result->recoveredAddress ?? NULL;

		if(!$result->valid || $token->address !== $recoveredAddress)
		{
			\SeanMorris\Ids\Log::debug('Challenge address does not match.');
			return FALSE;
		}

		if(!$existingSubscription = json_decode($redis->get('SUBS-' . $recoveredAddress)))
		{
			return FALSE;
		}

		if($existingSubscription->expiry < time())
		{
			return FALSE;
		}

		$github = new Github(\SeanMorris\Ids\Settings::read('github', 'token'));

		$assetPathParts = explode('/', $assetPath);
		$directoryPath  = implode('/', array_slice($assetPathParts, 0, -1));

		$repo  = 'seanmorris/ephsys-media-processor';
		$files = $github->getDir($repo, $directoryPath);

		foreach($files as $file)
		{
			if($file->path !== $assetPath)
			{
				continue;
			}

			$hash = substr($file->_links->git, -40);
			$blob = $github->getBlob($repo, $hash);
			$full = strlen($blob);

			ob_flush();
			ob_end_flush();

			header('HTTP/1.1 200 OK');
			header('Content-Length: ' . $full);
			header('Content-Type: ' . $mimeHeader);

			while($blob)
			{
				print substr($blob, 0, 1024*128);
				$blob = substr($blob, 1024*128);
			}

			die;
		}
	}
}
