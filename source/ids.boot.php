<?php

if($redisUrl = \SeanMorris\Ids\Settings::read('REDIS', 'URL'))
{
	$_ENV['REDIS_URL'] = $redisUrl;
}

if(!empty($_ENV['REDIS_URL']))
{
	$redisUrlParts = parse_url($_ENV['REDIS_URL']);
	\SeanMorris\Ids\Log::error($redisUrlParts);

	ini_set('session.save_handler', 'redis');
	ini_set(
		'session.save_path'
		,'tcp://'
			. $redisUrlParts['host']
			. ':'
			. $redisUrlParts['port']
			. (!empty($redisUrlParts['pass'])
				? ('/?auth=' . $redisUrlParts['pass'])
				: null
			)
	);

	\SeanMorris\Ids\Settings::register('redis', function() use($redisUrlParts){
		$redis = new Redis();
		$redis->connect($redisUrlParts['host'], $redisUrlParts['port']);
		$redis->auth($redisUrlParts['pass']);
		return $redis;
	});
}

session_start();
