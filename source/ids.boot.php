<?php

if(!empty($_ENV['REDIS_URL']))
{
	$redisUrlParts = parse_url($_ENV['REDIS_URL']);
	\SeanMorris\Ids\Log::error($redisUrlParts);
	header('Set-Cookie: PHPSESSID='.$_COOKIE["PHPSESSID"].'; SameSite=None');
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
