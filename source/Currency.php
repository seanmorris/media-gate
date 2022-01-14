<?php
namespace SeanMorris\MediaGate;

class Currency
{
	public function __construct($value)
	{
		$this->value  = $value;
		$this->amount = 0;
		$this->code   = '!!ERROR!!';

		if(preg_match('/([A-Za-z]+)([\d\.]+)/', $value, $groups))
		{
			$this->code   = $groups[1];
			$this->amount = $groups[2];
		}
	}

	public function __toString()
	{
		return ((float)((string) $this->amount));
	}
}
