<?php
namespace SeanMorris\MediaGate;

class Github
{
	protected $token;

	public function __construct($token)
	{
		$this->token = $token;
	}

	public function getDir($repo, $location)
	{
		$response = $this->httpRequest('https://api.github.com/repos/'
			. $repo
			. '/contents/'
			. $location
		);

		return json_decode($response);
	}

	public function getFile($repo, $location)
	{
		$response = $this->httpRequest('https://api.github.com/repos/'
			. $repo
			. '/contents/'
			. $location
		);

		$response = json_decode($response);
		$content  = base64_decode($response->content);

		return $content;
	}

	public function getBlob($repo, $hash)
	{
		$response = $this->httpRequest('https://api.github.com/repos/'
			. $repo
			. '/git/blobs/'
			. $hash
		);

		$response = json_decode($response);
		$content  = base64_decode($response->content);

		return $content;
	}

	protected function httpRequest($url, $method = 'GET')
	{
		$options = ['http' => ['method'  => $method, 'header' => [
			'Authorization: Bearer ' . $this->token,
			'User-Agent: request'
		]]];

		$context = stream_context_create($options);
		$stream  = fopen($url, 'r', false, $context);

		$meta = stream_get_meta_data($stream);
		$data = stream_get_contents($stream);

		return $data;
	}
}
