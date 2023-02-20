<?php
/*
 * Telegram.php
 * ------------
 * Bot Telegram communication logic.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

if ($useTelegram) {
	$nowTime = new \DateTime("now");
	
	// Startup message
	if ($timer["telegram"] == 0) {
		$send = $telegram->sendMessage(
			$telegramTarget,
			"Karura " . VERSION . " is now online.\n" .
			"Use /help for available commands.\n" .
			"Currency pair: {$coin}/{$fiat}\n\n" .
			"AvgPrice: {$avgPrice} {$fiat}\n" .
			"Buy Factor: {$buyFactor}\n" .
			"Sell Factor: {$sellFactor}"
		);
		$log->rec("Sent bot online message.");
		$timer["telegram"] = $timer["current"] + $telegramInterval;
	}
	
	/*
	 * Regular update messages, now with sleep protection!
	 */
	
	// Recalculate times
	if ($muteEndTime < $nowTime) {
		$muteBeginningTime = \DateTime::createFromFormat('H:i', $muteBeginning);
		$muteEndTime = \DateTime::createFromFormat('H:i', $muteBeginning)
						->add(date_interval_create_from_date_string($muteTime));
	}
	
	if ($timer["current"] >= $timer["telegram"]) {
		if ($telegramMute) {
			if ($muteBeginningTime > $nowTime or $muteEndTime < $nowTime) {
				$send = $telegram->sendMessage(
					$telegramTarget,
					"AvgPrice: {$avgPrice} {$fiat}\n" .
					"Buy Factor: {$buyFactor}\n" .
					"Sell Factor: {$sellFactor}"
				);
				$timer["telegram"] = $timer["current"] + $telegramInterval;
			}
		} else {
			$send = $telegram->sendMessage(
				$telegramTarget,
				"AvgPrice: {$avgPrice} {$fiat}\n" .
				"Buy Factor: {$buyFactor}\n" .
				"Sell Factor: {$sellFactor}"
			);
			$timer["telegram"] = $timer["current"] + $telegramInterval;
		}
	}
	
	// Commands
	$receive = $telegram->getUpdates();
	$last = end($receive["result"]);
	
	$lastUpdate = $last["update_id"];

	if ($updateId < $lastUpdate) {
		$reset = $telegram->resetUpdates();
		
		$end = end($receive["result"]);
		$receiver = $end["message"]["chat"]["id"];
		$input = $end["message"]["text"];
		$messageType = $end["message"]["entities"][0]["type"];
		
		if ($messageType == "bot_command") {
			switch ($input) {
				case "/buy":
					$send = $telegram->sendMessage(
						$receiver,
						"/buy is not yet implemented."
					);
					break;
				case "/help":
					$send = $telegram->sendMessage(
						$receiver,
						"Available commands:\n" . 
						"/buy: not yet implemented.\n" .
						"/help: prints available commands.\n" .
						"/indicators: display indicators.\n" .
						"/info: lists info about the bot.\n" .
						"/sell: not yet implemented.\n" .
						"/sr: support and resistance info.\n" .
						"/status: current price & buy/sell factors"
					);
					break;
				case "/indicators":
					$message = "";
					if ($crsiInterval != 0) {
						$message .=
						"CRSI time: {$crsiTimeUnit}\n" .
						"CRSI: {$crsi}\n";
					}
					if ($rsiInterval != 0) {
						$message .=
						"RSI period: {$rsiPeriod}\n" .
						"RSI time: {$rsiTimeUnit}\n" .
						"RSI: {$rsi}\n";
					}
					$send = $telegram->sendMessage($receiver, $message);
					break;
				case "/info":
					$upTime = $startTime->diff($nowTime)->format('%yy, %ad, %hh, %im, %ss');
					$send = $telegram->sendMessage(
						$receiver,
						"Karura version: " . VERSION . "\n" .
						"Currency pair: {$coin}/{$fiat}\n" .
						"Uptime: {$upTime}"
					);
					break;
				case "/sell":
					$send = $telegram->sendMessage(
						$receiver,
						"/sell is not yet implemented."
					);
					break;
				case "/sr":
					$send = $telegram->sendMessage(
						$receiver,
						"AvgPrice: {$avgPrice}\n" .
						"R3: {$R3}\n" .
						"R2: {$R2}\n" .
						"R1: {$R1}\n" .
						"PP: {$PP}\n" .
						"S1: {$S1}\n" .
						"S2: {$S2}\n" .
						"S3: {$S3}"
					);
					break;
				case "/status":
					$send = $telegram->sendMessage(
						$receiver,
						"AvgPrice: {$avgPrice} {$coin}/{$fiat}\n" .
						"Buy Factor: {$buyFactor}\n" .
						"Sell Factor: {$sellFactor}"
					);
					break;
				default:
					$send = $telegram->sendMessage(
						$receiver,
						"Command not recognized."
					);
					break;
			}
			$log->rec("Received {$input} command.");
			$updateId = $lastUpdate;
		}
	}
}

?>
