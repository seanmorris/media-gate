<?php
namespace SeanMorris\MediaGate;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Settings;
use \SessionHandlerInterface, \SessionIdInterface;

class SessionHandler extends \SessionHandler implements SessionHandlerInterface, SessionIdInterface
{
	public function __construct()
	{
		$this->redis = Settings::get('redis');
	}

	public function open($savePath, $sessionName)
	{
		$this->sessionName = $sessionName;
		$this->savePath    = $savePath;

		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($sessionId)
	{
		$userData = $this->redis->get('sess_' . $sessionId);

		if($userData)
		{
			return $userData;
		}

		return serialize([]);
	}

	public function write($sessionId, $userData)
	{
		$this->redis->set('sess_' . $sessionId, $userData);

		return true;
	}

	public function destroy($sessionId)
	{
		$this->redis->del('sess_' . $sessionId);

		return true;
	}

	public function gc($lifetime)
	{
		return 365 * 24 * 60 * 60;
	}
}
