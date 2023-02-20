<?php
/*
 * TelegramApi.php
 * ---------------
 * Telegram curl wrapper.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

namespace Telegram;

class TelegramApi {
	protected $url;
	
	function __construct(int $botId, string $botHash) {
		$this->url = "https://api.telegram.org/bot" . $botId . ":" . $botHash;
	}
	
	/*
	 * protected function request(string $function, array $data = [])
	 *
	 * Does the actual request.
	 */
	
	protected function request(string $function, array $data = []) {
		$curl = curl_init();
		
		curl_setopt_array(
			$curl,
			[	
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_URL => $this->url . "/" . $function . "?" . http_build_query($data),
			]
		);
		
		$response = json_decode(curl_exec($curl), true);
		curl_close($curl);

		return $response;
	}
	
	/*
	 * public function getUpdates(int $offset)
	 * 
	 * Get the messages sent to the bot.
	 */
	 
	public function getUpdates(int $offset = 0) {
		return $this->request("getUpdates", ["offset" => $offset]);
	}
	
	/*
	 * public function resetUpdates()
	 *
	 * Used to reset the messages queue to keep it small.
	 */
	 
	public function resetUpdates() {
		return $this->getUpdates(-1);
	}
	
	/*
	 * public function sendMessage(int $chatId, string $message)
	 *
	 * Does what it says on the tin.
	 */

	public function sendMessage(int $chatId, string $message) {
		return $this->request(
			"sendMessage",
			["chat_id" => $chatId, "text" => $message]
		);
	}
}

?>
