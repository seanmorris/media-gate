<?php
namespace SeanMorris\MediaGate;

class Transaction
{
	protected
		$timestamp
		, $blockNumber
		, $hash
		, $nonce
		, $blockHash
		, $transactionIndex
		, $from
		, $to
		, $value
		, $gas
		, $gasPrice
		, $isError
		, $txreceipt_status
		, $input
		, $contractAddress
		, $cumulativeGasUsed
		, $gasUsed
		, $confirmations;

	public static function loadIncoming()
	{
		$incomingAddress = \SeanMorris\Ids\Settings::read('ETH','RECEIPT','ADDRESS');
		$startBlock = \SeanMorris\Ids\Settings::read('ETHERSCAN','START','BLOCK');
		$apiKey = \SeanMorris\Ids\Settings::read('ETHERSCAN','API', 'KEY');

		$http = new Http;

		$response = $http->httpRequest(sprintf(
			'https://api.etherscan.io/api'
				. '?module=account'
				. '&address=%s'
				. '&startblock=%d'
				. '&apikey=%s'
				. '&action=txlist'
				. '&sort=desc'
			, $incomingAddress
			, $startBlock
			, $apiKey
		));

		return json_decode($response);
	}
}
