<?php
/*
 * BinanceApi.php
 * --------------
 * API interface for Binance.
 *
 * Copyright (c) Thijs van der Woude, 2021
 */

namespace Exchange;

class BinanceApi extends ExchangeApi {
	// Spot test network URL
	protected $testUrls = [
		"https://testnet.binance.vision/",
	];

	// All possible endpoints
	protected $apiUrls = [
		"https://api.binance.com/",
		"https://api1.binance.com/",
		"https://api2.binance.com/",
		"https://api3.binance.com/",
	];

	protected $testNet;

	// API variables
	protected $apiKey, $privateKey;

	// Time offset for server sync
	protected $timeOffset;

	// Function variables
	protected $method;
	protected $signed = false;
	protected $path;
	protected $data = [];
	protected $timestamp = false;

	function __construct(string $apiKey, string $privateKey, \Logger $log, bool $testNet = false) {
		$this->log = $log;

		// Switch accordingly between normal or testnet.
		$this->testNet = $testNet;
		$this->switchNet();

		// First, check which endpoint is online
		$online = $this->checkOnline($this->urls);
		if ($online == -1) {
			$this->log->rec("Binance is not online? print_r(urls):", 1, 1);
			$this->log->rec(print_r($urls, true), 1, 1);
			$this->reconnect();
		}
		$this->baseUrl = $this->urls[$online];

		$this->apiKey = $apiKey;
		$this->privateKey = $privateKey;

		// Sync with server time
		$this->timeOffset = round(microtime(true) * 1000) - $this->checkServerTime();
		$this->log->rec("Performed server sync.", 0, 1);
	}

	protected function request() {
		$curl = curl_init();
		
		if ($this->timestamp) {
            $this->data['timestamp'] = number_format((microtime(true) * 1000) + $this->timeOffset, 0, '.', '');
		}

		$query = http_build_query($this->data, '', '&');

		if ($this->signed) {
			$query = http_build_query($this->data, '', '&');

			$signature = hash_hmac('sha256', $query, $this->privateKey);
			if ($this->method === POST) {
				$endpoint = $this->baseUrl . $this->path;
				$this->data["signature"] = $signature;
				$query = http_build_query($this->data, '', '&');
			} else {
				//print_r($this->baseUrl . $this->path . '?' . $query . '&signature=' . $signature . NEWLN);
				$endpoint = $this->baseUrl . $this->path . '?' . $query . '&signature=' . $signature;
			}
			curl_setopt_array($curl, [
				CURLOPT_URL => $endpoint,
            	CURLOPT_HTTPHEADER => ['X-MBX-APIKEY: ' . $this->apiKey . "\r\n"],
			]);
		} else if (count($this->data) > 0) {
			//print_r($this->baseUrl . $this->path . '?' . $query . NEWLN);
			curl_setopt($curl, CURLOPT_URL, $this->baseUrl . $this->path . '?' . $query);
		} else {
			//print_r($this->baseUrl . $this->path . NEWLN);
            curl_setopt_array($curl, [
				CURLOPT_URL => $this->baseUrl . $this->path,
            	CURLOPT_HTTPHEADER => ['X-MBX-APIKEY: ' . $this->apiKey],
			]);
        }

		// Method-specific curl options
		switch ($this->method) {
			case DELETE:
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
				break;
			case POST:
				curl_setopt_array($curl, [
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $query,
				]);
				break;
			case PUT:
				curl_setopt($curl, CURLOPT_PUT, true);
				break;
		}

		// Curl options that are always the same
		curl_setopt_array($curl, [
			CURLOPT_URL => $this->baseUrl . $this->path . "?" . $query,
			CURLOPT_USERAGENT => "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			//CURLOPT_HEADER => true,
			CURLOPT_FOLLOWLOCATION => true,
		]);
		
		// Make the request
		$response = json_decode(curl_exec($curl), true);

		// Set a possible error code
		$this->error = curl_errno($curl);

		/*
		 * Check if the endpoint is still working
		 * 
		 * https://curl.se/libcurl/c/libcurl-errors.html
		 * 
		 * CURLE_COULDNT_RESOLVE_HOST (6)
		 * 		Couldn't resolve host. The given remote host was not resolved. 
		 */
		if ($this->error == 6) {
			$this->log->rec("Received CURLE_COULDNT_RESOLVE_HOST (6)...", 1, 1);
			$this->reconnect();
		}

		if (is_array($response)) {
			if (array_key_exists("code", $response)) {
				$this->log->rec("ERROR CODE IN RESPONSE (print_r):");
				$this->log->rec(print_r($response, true));
			}
		}

		// Clean up the function variables.
		$this->data = [];
		$this->signed = false;
		$this->method = "";
		$this->timestamp = false;

		curl_close($curl);
		return $response;
	}

	public function getSystemStatus() {
		$this->method = GET;
		$this->path = "sapi/v1/system/status";
		return $this->request()["msg"];
	}
	
	public function accountStatus() {
		$this->method = GET;
		$this->path = "wapi/v3/accountStatus.html";
		$this->timestamp = true;
		$this->data = [
			"recvWindow" => 10000,
		];
		return $this->request();
	}

	public function testConnectivity() {
		$this->method = GET;
		$this->path = "api/v3/ping";
		if ($this->request() == []) {
			return true;
		} else {
			return false;
		}
	}

	public function checkServerTime() {
		$this->method = GET;
		$this->path = "api/v3/time";
		return $this->request()["serverTime"];
	}

	public function exchangeInformation() {
		$this->method = GET;
		$this->path = "api/v3/exchangeInfo";
		return $this->request();
	}

	public function orderBook(string $pair, int $limit = 100) {
		$this->method = GET;
		$this->path = "api/v3/depth";
		$this->data = [
			"symbol" => $pair,
			"limit" => $limit,
		];
		return $this->request();
	}

	public function recentTrades(string $pair, int $limit = 500) {
		$this->method = GET;
		$this->path = "api/v3/trades";
		$this->data = [
			"symbol" => $pair,
			"limit" => $limit,
		];
		return $this->request();
	}

	public function currentAvgPrice(string $pair) {
		$this->method = GET;
		$this->path = "api/v3/avgPrice";
		$this->data = [
			"symbol" => $pair,
		];
		return $this->request();
	}

	public function historicalTrades(string $pair, int $fromId = 0, int $limit = 500) {
		$this->method = GET;
		$this->path = "api/v3/historicalTrades";
		$this->signed = true;
		$this->data = [
			"symbol" => $pair,
			//"limit" => $limit,
			//"fromId" => $fromId,
		];
		return $this->request();
	}

	public function kline(string $pair, int $limit = 500, string $interval = "1m") {
		$this->method = GET;
		$this->path = "api/v3/klines";
		$this->data = [
			"symbol" => $pair,
			"interval" => $interval,
			"limit" => $limit,
		];
		return $this->request();
	}

	public function tfhTickerPriceChangeStats(string $pair) {
		$this->method = GET;
		$this->path = "api/v3/ticker/24hr";
		$this->data = [
			"symbol" => $pair,
		];
		return $this->request();
	}

	/*
	 * public function switchNet
	 * 
	 * 
	 */

	public function switchNet() {
		switch ($this->testNet) {
			case false:
				$this->urls = $this->apiUrls;
				$this->log->rec("Using Binance normal net.", 0, 1);
				break;
			case true:
				$this->urls = $this->testUrls;
				$this->log->rec("Using Binance Testnet.", 0, 1);
				break;
		}
	}

	public function getNet() {
		switch ($this->testNet) {
			case false:
				return "normal";
			case true:
				return "testnet";
		}
	}
}

?>
