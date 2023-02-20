<?php
// Bot mode? (report/paper/trade)
$mode = "report";

// Exchange? (binance)
$exchange = "binance";

// Timezone
$timezone = "Europe/Amsterdam";

// Currency pair
$coin = "";
$fiat = "";

// Log to file? (true/false)
$logToFile = false;

// Market state formula (floor/fib)
$srFormula = "fib";

// Indicator weights
$msWeight = 0;
$crsiWeight = 0;
$rsiWeight = 1;

// CRSI
$crsiTimeUnit = "1m";	// Time per candlestick

// RSI
$rsiPeriod = 14;		// Amount of candlesticks
$rsiTimeUnit = "30m";	// Time per candlestick

// Intervals
$msInterval = 0;			// 24 hours
$priceInterval = 15;
$vppInterval = 0;
$crsiInterval = 0;
$rsiInterval = 15;
$msfInterval = 0;
$bfInterval = 15;
$sfInterval = 15;

/*
 * Binance
 */
$binanceApiKey = "";			// API key
$binanceApiPrivateKey = "";		// Private key

?>
