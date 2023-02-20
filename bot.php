<?php
/*
 * bot.php
 * -------
 * Bot main entry point.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

require_once "config.php";

require "lib/Algorithms.php";
require "lib/Constants.php";
require "lib/DieX.php";
require "lib/Dump.php";
require "lib/Logger.php";
require "lib/Maths.php";

// Exchanges
include "api/exchange/ExchangeApi.php";
require "api/exchange/binance/BinanceApi.php";

// Telegram
require "TelegramConfig.php";
require "api/telegram/TelegramApi.php";

// Indicators
require "indicators/CRSI.php";
require "indicators/ROC.php";
require "indicators/RSI.php";

/*
 * Time stuff.
 */

// Set the timezone
date_default_timezone_set($timezone);

if ($useTelegram) {
	$nowTime = new \DateTime("now");
	$muteBeginningTime = \DateTime::createFromFormat('H:i', $muteBeginning);
	$muteEndTime = \DateTime::createFromFormat('H:i', $muteBeginning)
					->add(date_interval_create_from_date_string($muteTime));
}

// Startup time
$startTime = new \DateTime("now");

/*
 * Argument handling
 */

if ($argc > 1) {
	$shortArgs = "dv";	// No values
	$arguments = getopt($shortArgs);
	
	if (array_key_exists("d", $arguments)) {
		echo "DYNO TO BE IMPLEMENTED";
		return 0;
	}
	if (array_key_exists("v", $arguments)) {
		echo "Version " . VERSION;
		return 0;
	}
}

// Display bot sign :D
echo "\n";
echo "   ____  __                                       \n";
echo "  |    |/ _|_____  _______  __ __ _______ _____   \n";
echo "  |      <  \__  \ \_  __ \|  |  \\_   __ \\__   \ \n";
echo "  |    |  \  / __ \_|  | \/|  |  / |  | \/ / __ \_\n";
echo "  |____|__ \(____  /|__|   |____/  |__|   (____  /\n";
echo "          \/     \/                            \/ \n";
echo "\n";

/*
 * Bot initialization
 */

$log = new \Logger($logToFile);

$log->rec("Bot version: " . VERSION);

$log->rec("Initializing bot...");

$currencyPair = $coin . $fiat;
$log->rec("Currency pair: {$coin}/{$fiat}");

// Check if curl is enabled
if (!function_exists("curl_version")) {
	$log->rec("curl is not enabled.", 1);
	die();
}

// Open connection with Telegram
if ($useTelegram) {
	$telegram = new \Telegram\TelegramApi($botId, $botHash);
	
	$receive = $telegram->getUpdates()["result"];
	$last = end($receive);
	
	$updateId = $last["update_id"];

	$log->rec("Telegram connection opened.");
} else {
	$log->rec("Config says use do not use Telegram.");
}

// Open connection with exchange
switch ($exchange) {
	case "binance":
		$api = new Exchange\BinanceApi($binanceApiKey, $binanceApiPrivateKey, $log, $binanceTestNet);
		$log->rec("Connected to: " . $api->getUrl());
		break;
}

//dump($api->historicalTrades("LTOEUR"));
//dump($api->accountStatus());

// Exchange status
$exchangeStatus = $api->getSystemStatus();
if ($exchangeStatus != "normal" and $binanceTestNet != true) {
	$log->rec("exchange reports status other than normal.", 1);
	exit(1);
} else {
	$log->rec("Exchange status: " . $exchangeStatus);
}

if ($msInterval != 0) {
	$log->rec("MS formula: {$srFormula}");
}

$factors = [
	"msFactor" => 0,
	"crsiFactor" => 0,
	"rsiFactor" => 0,
];

$timer = [
	"ms" => 0,
	"msf" => 0,
	"price" => 0,
	"vpp" => 0,
	"crsi" => 0,
	"rsi" => 0,
	"bf" => 0,
	"sf" => 0,
	"telegram" => 0,
];

// Bot main loop
while (true) {
	$timer["current"] = time();

	// Price
	if ($timer["current"] >= $timer["price"]) {
		$avgPrice = calcAvgPrice($api->recentTrades($currencyPair, 20));
		
		$timer["price"] = $timer["current"] + $priceInterval;
		
		$log->rec("AvgPrice = {$avgPrice}");
	}

	// Market State
	if ($timer["current"] >= $timer["ms"] and $msInterval != 0) {		
		$log->rec("--------------");
		$tfh = $api->tfhTickerPriceChangeStats($currencyPair);

		$high = $tfh["highPrice"];
		$low = $tfh["lowPrice"];
		$close = $tfh["prevClosePrice"];

		$R3 = calcR3($high, $low, $close, $srFormula);
		$R2 = calcR2($high, $low, $close, $srFormula);
		$R1 = calcR1($high, $low, $close, $srFormula);
		$PP = calcPP($high, $low, $close);
		$S1 = calcS1($high, $low, $close, $srFormula);
		$S2 = calcS2($high, $low, $close, $srFormula);
		$S3 = calcS3($high, $low, $close, $srFormula);

		if ($avgPrice < $R3 and $avgPrice > $R2) {
			$marketState = "R3R2";
		} elseif ($avgPrice < $R2 and $avgPrice > $R1) {
			$marketState = "R2R1";
		} elseif ($avgPrice < $R1 and $avgPrice > $PP) {
			$marketState = "R1PP";
		} elseif ($avgPrice < $PP and $avgPrice > $S1) {
			$marketState = "PPS1";
		} elseif ($avgPrice < $S1 and $avgPrice > $S2) {
			$marketState = "S1S2";
		} else {
			$marketState = "S2S3";
		}

		$timer["ms"] = $timer["current"] + $msInterval;

		$log->rec("HI: {$high}");
		$log->rec("LO: {$low}");
		$log->rec("CL: {$close}");

		$log->rec("R3: {$R3}");
		$log->rec("R2: {$R2}");
		$log->rec("R1: {$R1}");
		$log->rec("PP: {$PP}");
		$log->rec("S1: {$S1}");
		$log->rec("S2: {$S2}");
		$log->rec("S3: {$S3}");

		$log->rec("MS: {$marketState}");
		$log->rec("--------------");
	}

	// Market State Factor
	if ($timer["current"] >= $timer["msf"] and $msfInterval != 0) {
		if ($avgPrice > $PP) {
			$range = $R3 - $PP;
			$priceAdjusted =  $PP - $avgPrice;
			$factors["msFactor"] = ($priceAdjusted / $range) * 100;
		} else {
			$range = $PP - $S3;
			$priceAdjusted = $PP - $avgPrice;
			$factors["msFactor"] = ($priceAdjusted / $range) * 100;
		}

		$timer["msf"] = $timer["current"] + $msfInterval;;

		$log->rec("msF: {$factors["msFactor"]}");
	}

	// VPP
	if ($timer["current"] >= $timer["vpp"] and $vppInterval != 0) {
		$vpp = volumePriceProspect($api->orderBook($currencyPair), 1000);
		
		$timer["vpp"] = $timer["current"] + $vppInterval;
		
		$log->rec("VPP: {$vpp}");
	}

	// RSI + RSI Factor
	if ($timer["current"] >= $timer["rsi"] and $rsiInterval != 0) {
		$rsi = rsi($api->kline($currencyPair, $rsiPeriod * 2, $rsiTimeUnit));
		$factors["rsiFactor"] = $rsi;
		
		$timer["rsi"] = $timer["current"] + $rsiInterval;
		
		$log->rec("rsi: {$rsi}");
		//$log->rec("rsiF: {$factors["rsiFactor"]}");
	}
	
	if ($timer["current"] >= $timer["crsi"] and $crsiInterval != 0) {
		$crsi = crsi($api->kline($currencyPair, 6, $crsiTimeUnit));
		$factors["crsiFactor"] = $crsi;
		
		$timer["crsi"] = $timer["current"] + $crsiInterval;
		
		$log->rec("crsi: {$crsi}");
		//$log->rec("crsiF: {$factors["crsiFactor"]}");
	}

	// Buy Factor
	if ($timer["current"] >= $timer["bf"]) {
		$buyFactor =
			($crsiWeight * (100 - $factors["crsiFactor"])) +
			($rsiWeight * (100 - $factors["rsiFactor"])) +
			($msWeight * $factors["msFactor"]);
		
		$timer["bf"] = $timer["current"] + $bfInterval;

		$log->rec("bF: {$buyFactor}");
	}

	// Sell factor
	if ($timer["current"] >= $timer["sf"]) {
		$sellFactor =
			($crsiWeight * $factors["crsiFactor"]) +
			($rsiWeight * $factors["rsiFactor"]) +
			($msWeight * $factors["msFactor"]);
		
		$timer["sf"] = $timer["current"] + $sfInterval;

		$log->rec("sF: {$sellFactor}");
	}
	
	// Telegram handling
	include "lib/Telegram.php";

	sleep(1);
}

return 0;

?>
