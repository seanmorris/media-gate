<?php

if($_ENV['REDIS_URL'])
{
	\SeanMorris\Ids\Log::error($redisUrlParts);

	$redisUrlParts = parse_url($_ENV['REDIS_URL']);
	ini_set('session.save_handler', 'redis');
	ini_set(
		'session.save_path'
		,'tcp://'
			. $redisUrlParts['host']
			. ':'
			. $redisUrlParts['port']
			. '?auth='
			. $redisUrlParts['pass']
	);
}

session_start();
