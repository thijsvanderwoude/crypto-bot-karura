<?php
/*
 * RSI.php
 * -------
 * RSI indicator implementation.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

// https://nullbeans.com/how-to-calculate-the-relative-strength-index-rsi/#How_to_calculate_the_RSI
function rsi(array $klines) {
	// Calculate changes
	$upChange = [];
	$downChange = [];

	for ($i = 0; $i < sizeof($klines); $i++) {
		if ($klines[$i][4] > $klines[$i][1]) {
			array_push($upChange, $klines[$i][4] - $klines[$i][1]);
			array_push($downChange, 0);
		} else {
			array_push($downChange, $klines[$i][1] - $klines[$i][4]);
			array_push($upChange, 0);
		}
	}

	// Calculate Simple Moving Average for SMMA(0)
	$upSMMA[0] = array_sum(array_slice($upChange, 0, sizeof($upChange) / 2)) / (sizeof($upChange) / 2);
	$downSMMA[0] = array_sum(array_slice($downChange, 0, sizeof($downChange) / 2)) / (sizeof($downChange) / 2);

	// Calculate SMMA up and down
	$i = 1;
	for ($n = sizeof($klines) / 2 - 1; $n < sizeof($klines); $n++) {
		$upSMMA[$i] = ($upChange[$n] + ($upSMMA[$i - 1] * (sizeof($klines) / 2 - 1))) / (sizeof($klines) / 2);
		$downSMMA[$i] = ($downChange[$n] + ($downSMMA[$i - 1] * (sizeof($klines) / 2 - 1))) / (sizeof($klines) / 2);
		
		$i++;
	}

	$rs = $upSMMA[sizeof($upSMMA) - 1] / $downSMMA[sizeof($downSMMA) - 1];

	return 100 - (100 / (1 + $rs));
}

?>
