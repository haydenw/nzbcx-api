<?php

namespace NZBCX_API;

use NZBCX_API\Exceptions\ExceptionAPICallFailure;
use NZBCX_API\Exceptions\ExceptionAuthenticationFailure;

class ApiHttpRequestor {

	public $userAgent = false;
	public $certificateFileName = false;
	public $apiKey;
	public $apiSecret;
	public $userId;

	protected $timestamp = 0;
	protected $requests = 0;
	public $ratelimit = 600; // Calls per $checkFrequency
	public $checkFrequency = 600; // Check frequency in seconds

	/**
	 * Create new instance of Api
	 */
	public function __construct($apiKey, $apiSecret, $userId) {
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
		$this->userId = $userId;
	}

	/**
	 * Make Http request
	 *
	 * @param string $url
	 * @param array $postData
	 * @return mixed
	 */
	public function request($url, $postData = false, $authRequired = false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->userAgent)
			curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
		$certificateFilePath = dirname(dirname(__FILE__)) . '/certs/' . $this->certificateFileName;
		if ($this->certificateFileName && file_exists($certificateFilePath)) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        	curl_setopt($ch, CURLOPT_CAINFO, $certificateFilePath);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		if ($authRequired && $authPostData = $this->getAuthPostData()) {
			$postData = array_merge((is_array($postData) ? $postData : []), $authPostData);
		}
		if (!empty($postData)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$this->ratelimit();
		if ($info['http_code'] == 200) {
			if ($info['content_type'] == 'application/json') {
				$result = json_decode($result);
				if (is_object($result) && property_exists($result, 'error')) {
					if (property_exists($result->error, 'nonce')) {
						throw new ExceptionAuthenticationFailure("API authentication error: Nonce ".$result->error->nonce);
					} else if (property_exists($result->error, 'key')) {
						if (is_array($result->error->key)) $result->error->key = implode(", ", $result->error->key);
						throw new ExceptionAuthenticationFailure("API authentication error: Key ".$result->error->key);
					} else if (property_exists($result->error, 'signature')) {
						throw new ExceptionAuthenticationFailure("API authentication error: Signature ".$result->error->signature);
					} else if (is_string($result->error)) {
						throw new ExceptionAPICallFailure("API call returned error ".$result->error);
					} else {
						return $result;
					}
				} else {
					return $result;
				}
			} else {
				return $result;
			}
		} else {
			throw new ExceptionAPICallFailure("API call returned HTTP ".$info['http_code']);
		}
	}

	protected function getAuthPostData() {
		if (!empty($this->apiKey) && !empty($this->apiSecret) && !empty($this->userId)) {
			$nonce = strval(time());
			$signature = hash_hmac('sha256', $nonce.$this->userId.$this->apiKey, $this->apiSecret);
			$postData = [
				'nonce' => $nonce,
				'key' => $this->apiKey,
				'signature' => $signature
			];
			return $postData;
		} else {
			throw new ExceptionAuthenticationFailure("Missing API authentication data");
		}
	}

	/**
	 * Get microtime as float
	 *
	 * @return float
	 */
	protected function getMicroTime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * Call after every request
	 */
	protected function rateLimit() {
		sleep(1);
		$this->requests++;
		if ($this->requests >= $this->ratelimit) {
			$newtime = $this->getMicroTime();
			if ($newtime - $this->timestamp > $this->checkFrequency) {
				$diff = ($this->checkFrequency - ($newtime - $this->timestamp)) * 1000000;
				usleep($diff);
			}
			$this->timestamp = $this->getMicroTime();
			$this->requests = 0;
		}
	}
}