<?php
namespace SeanMorris\MediaGate;

class Http
{
	public function httpRequest($url, $method = 'GET')
	{
		$options = ['http' => ['method'  => $method, 'header' => [
			'User-Agent: request'
		]]];

		$context = stream_context_create($options);
		$stream  = fopen($url, 'r', false, $context);

		$meta = stream_get_meta_data($stream);
		$data = stream_get_contents($stream);

		return $data;
	}
}
