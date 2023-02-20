<?php
/*
 * ROC.php
 * -------
 * Rate-Of-Change indicator implementation.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

// https://www.tradingtechnologies.com/xtrader-help/x-study/technical-indicator-definitions/rate-of-change-roc/
function roc(array $klines) {
	$oldPrice = $klines[0][4];
	$currentPrice = end($klines)[0][4];
	return ($currentPrice / $oldPrice) * 100;
}

?>
