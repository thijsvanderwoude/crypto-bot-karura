<?php
/*
 * CRSI.php
 * -------
 * CRSI indicator implementation.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

// https://www.tradingview.com/support/solutions/43000502017-connors-rsi-crsi/
function crsi(array $klines) {
	// Calculate RSI
	$rsi = rsi($klines);
	
	// Updown is a fraud, really. Impossible to figure out anywhere online.
	$twoPeriods = array_slice($klines, -4);
	$upDown = rsi($twoPeriods);
	
	// Rate Of Change in the last 3 candlesticks
	$threePeriods = array_slice($klines, -3);
	$roc = roc($threePeriods);
	
	return ($rsi + $upDown + $roc) / 3;
}

?>
