<?php
/*
 * Standard API class which provides general functionality.
 *
 * Copyright (c) Thijs van der Woude, 2021
 */

namespace Exchange;

/*
 * class ExchangeApi
 * 
 * Should NOT be used as an object on it's own. ALWAYS to be extended.
 */

class ExchangeApi {
    // CURL error
	protected $error = 0;	// Has to be set in advance to prevent possible abuse
	
	protected $urls;

	// Base url endpoint
	protected $baseUrl;

	// Logger object passed during construction
	protected $log;

    /*
	 * protected function checkOnline(array $urls)
	 *
	 * Checks which exchange URL is online and returns the arrays index,
	 * or -1 if none are online. To be run BEFORE request() is set up.
	 */
	
	protected function checkOnline() {
		$curl = curl_init();
		foreach ($this->urls as $key => $url) {
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 10,
    			CURLOPT_CONNECTTIMEOUT => 10,
			]);
			
			curl_exec($curl);

			/*
			 * https://curl.se/libcurl/c/libcurl-errors.html
			 * 
			 * CURLE_OK (0)
			 * 		All fine. Proceed as usual.
			 */

			switch (curl_errno($curl)) {
				case 0:
					return $key;
			}
		}
		
		curl_close($curl);
		return -1;
	}

    /*
	 * public function reconnect()
	 * 
	 * Used in case the current endpoint doesn't respond and we need to find
	 * a new one.
	 */

	public function reconnect() {
		// Loop endlessly until we find a working endpoint.
		while (true) {
			$this->log->rec("Attempting reconnect...", 0, 1);

			$check = $this->checkOnline();

			if ($check != -1) {
				$this->log->rec("Found working endpoint: " . $this->urls[$check], 0, 1);
				$this->baseUrl = $this->urls[$check];
				return true;
			} else {
				$this->log->rec("Endpoints not responding.", 1, 1);
			}
			sleep(5);
		}
	}

	/*
	 * public function getUrl()
	 * 
	 * Just returns the current endpoint URL.
	 */

	public function getUrl() {
		return $this->baseUrl;
	}

	/*
	 * public function getError()
	 * 
	 * Just returns the current curl error code.
	 */

	public function getError() {
		return $this->error;
	}
}

?>
