<?php

class KrakenAPIException extends \ErrorException {};

define("PUB", false);
define("PRIV", true);

class KrakenAPI {
	protected $apiURL, $apiKey, $privateKey, $curl;
	
	/*
	 * Constructor
	 */
	function __construct(
		string $apiKey,
		string $privateKey
	){
		$this->apiURL = "https://api.kraken.com/";
		$this->apiKey = $apiKey;
		$this->privateKey = $privateKey;
		$this->curl = curl_init();
		
		curl_setopt_array($this->curl, [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0",	// GET FUCKED
            CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
		]);
	}

	/*
	 * Destructor
	 */
	function __destruct() {
        curl_close($this->curl);
    }
	
	protected function query(bool $privacy, string $method, array $request = []) {
		if ($privacy) {
			curl_setopt($this->curl, CURLOPT_URL, $this->apiURL . "0/private/" . $method);
			
			$nonce = explode(' ', microtime());
            $request['nonce'] = $nonce[1] . str_pad(substr($nonce[0], 2, 6), 6, '0');
			
			print_r($nonce);
			print_r($request);
			die();
			
		} else {
			curl_setopt($this->curl, CURLOPT_URL, $this->apiURL . "0/public/" . $method);
		}
		
		// Build POST data string
		$postData = http_build_query($request, '', '&');
		
		// Create request
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
		
		// Do the request
		$request = curl_exec($this->curl);
		if ($request === false) {
			throw new KrakenAPIException("CURL error: " . curl_error($this->curl));
		}
		
		$response = json_decode($request, true);
		if (!is_array($response)) {
			throw new KrakenAPIException("JSON error: could not decode");
		}
		
		return $response;
	}

	public function getServerTime() {
	return $this->query(PUB, "Time")["result"];
	}
	
	/*
	 * Possible status values include:
	 * online (operational, full trading available)
	 * cancel_only (existing orders are cancelable, but new orders cannot be created)
	 * post_only (existing orders are cancelable, and only new post limit orders can be submitted)
	 * limit_only (existing orders are cancelable, and only new limit orders can be submitted)
	 * maintenance (system is offline for maintenance)
	 */
	public function getSystemStatus() {
		return $this->query(PUB, "SystemStatus");		
	}
	
	public function getBalance() {
		return $this->query(PRIV, "Balance");
	}
	
}

?>
