<?php

\SeanMorris\Ids\Settings::register('github', 'token', function($token) {
	$github = \Github\Client::createWithHttpClient(
		new \Http\Client\Curl\Client()
	);

	$github->authenticate($token, null, \Github\AuthMethod::ACCESS_TOKEN);

	return $github;
});
