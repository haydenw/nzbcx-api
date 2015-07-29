<?php

namespace NZBCX_API;

use NZBCX_API\Exceptions\ExceptionAPICallFailure;
use NZBCX_API\Exceptions\ExceptionAuthenticationFailure;
use NZBCX_API\ApiHttpRequestor;

class NZBCX {

	protected $apiBaseUriLive = 'https://nzbcx.com/api/';
	protected $apiBaseUriTest = 'https://testnet.nzbcx.com/api/';
	protected $apiBaseUri = '';
	public $userAgent = 'NZBCX API Client';
	protected $http;

	/**
	 * Create new instance of NZBCX
	 */
	public function __construct($apiKey, $apiSecret, $userId, $testing = false) {
		$this->apiBaseUri = $testing ? $this->apiBaseUriTest : $this->apiBaseUriLive;
		$this->http = new ApiHttpRequestor($apiKey, $apiSecret, $userId);
		$this->http->userAgent = $this->userAgent;
		if (!$testing)
			$this->http->certificateFileName = 'nzbcx.crt';
	}

	/**
	 * Get account balance
	 *
	 * @return object
	 */
	public function accountBalance() {
		$url = $this->apiBaseUri . 'account/balance';
		return $this->http->request($url, false, true);
	}

	/**
	 * Get account transactions
	 *
	 * @param int $since optional system transaction sequence id to retrieve, returned as id in result set
	 * @param int $limit optional max tranactions to return
	 * @param string $sort optional asc or desc
	 * @return array
	 */
	public function accountTransactions($since = 0, $limit = 100, $sort = 'asc') {
		$urlQuery = http_build_query(['since' => $since, 'limit' => $limit, 'sort' => $sort]);
		$url = $this->apiBaseUri . 'account/transactions' . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url, false, true);
	}

	/**
	 * Get account executions
	 *
	 * @param int $minutes optional filter results to executions in last $minutes minutes
	 * @param int $hours optional filter results to executions in last $hours hours
	 * @param int $days optional filter results to executions in last $days days
	 * @param int $orderId optional filter by client order id
	 * @return array
	 */
	public function accountExecutions($minutes = 0, $hours = 1, $days = 0, $orderId = null) {
		$urlParams = ['minutes' => $minutes, 'hours' => $hours, 'days' => $days];
		if (!empty($orderId))
			$urlParams['order_id'] = $orderId;
		$urlQuery = http_build_query($urlParams);
		$url = $this->apiBaseUri . 'account/executions' . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url, false, true);
	}

	/**
	 * Get account orders
	 *
	 * @param string $status optional U, A (active), C (cancelled), P (pending), R (rejected)
	 * @param int $orderId optional filter by client order id
	 * @return array
	 */
	public function accountOrders($status = 'A', $orderId = null) {
		$urlParams = ['status' => $status];
		if (!empty($orderId))
			$urlParams['order_id'] = $orderId;
		$urlQuery = http_build_query($urlParams);
		$url = $this->apiBaseUri . 'account/orders' . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url, false, true);
	}

	/**
	 * Cancel order
	 *
	 * @param int $orderId id of order to cancel
	 * @return array
	 */
	public function accountOrderCancel($orderId) {
		$urlQuery = http_build_query(['order_id' => $orderId]);
		$url = $this->apiBaseUri . 'account/order/cancel' . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url, false, true);
	}

	/**
	 * New order
	 *
	 * @param string $tickerCode
	 * @param string $side BUY or SELL
	 * @param string $type LMT (Limit) or MKT (Market)
	 * @param float $price only required on LMT (limit) orders
	 * @param float $quantity
	 * @param string $orderId optional client order id
	 * @return object
	 */
	public function accountOrderNew($tickerCode, $side, $type, $price = null, $quantity, $orderId = null) {
		$urlParams = [
			'ticker' => $tickerCode,
			'side' => $side,
			'type' => $type,
			'quantity' => $quantity
			];
		if (!empty($price))
			$urlParams['price'] = $price;
		if (!empty($orderId))
			$urlParams['order_id'] = $orderId;
		$urlQuery = http_build_query($urlParams);
		$url = $this->apiBaseUri . 'account/new/order' . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url, false, true);
	}

	/**
	 * Get FID codes and values or single FID and value if $fid specified
	 *
	 * @param string $tickerCode
	 * @param string $fid
	 * @return array
	 */
	public function market($tickerCode, $fid = false) {
		$url = $this->apiBaseUri . 'market/' . $tickerCode . ($fid ? '/' . $fid : '');
		return $this->http->request($url);
	}

	/**
	 * Get ticker data
	 *
	 * @param string $tickerData
	 * @return array
	 */
	public function ticker($tickerCode) {
		$url = $this->apiBaseUri . 'ticker/' . $tickerCode;
		return $this->http->request($url);
	}

	/**
	 * Get orderbook bids and asks data
	 *
	 * @param string $tickerData
	 * @return array
	 */
	public function orderBook($tickerCode) {
		$url = $this->apiBaseUri . 'orderbook/' . $tickerCode;
		return $this->http->request($url);
	}

	/**
	 * Get trades
	 *
	 * @param string $tickerData
	 * @param int $hours
	 * @param int $minutes
	 * @param int $days
	 * @return array
	 */
	public function trades($tickerCode, $hours = 1, $minutes = 0, $days = 0) {
		$urlQuery = http_build_query(['hours' => $hours, 'minutes' => $minutes, 'days' => $days]);
		$url = $this->apiBaseUri . 'trades/' . $tickerCode . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url);
	}

	/**
	 * Get last traded
	 *
	 * @param string $tickerData
	 * @param int $max
	 * @return array
	 */
	public function last($tickerCode, $max = 1) {
		$urlQuery = http_build_query(['max' => $max]);
		$url = $this->apiBaseUri . 'last/' . $tickerCode . ($urlQuery ? '?'.$urlQuery : '');
		return $this->http->request($url);
	}

	/**
	 * Get last update
	 *
	 * @param string $tickerData
	 * @return array
	 */
	public function lastUpdate($tickerCode) {
		$url = $this->apiBaseUri . 'lastupdate/' . $tickerCode;
		return $this->http->request($url);
	}
}