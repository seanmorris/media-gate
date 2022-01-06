<?php

\SeanMorris\Ids\Settings::register('github', 'token', function($token) {
	$github = new \Github\Client();

	$github->authenticate($token, null, \Github\AuthMethod::ACCESS_TOKEN);

	return $github;
});
