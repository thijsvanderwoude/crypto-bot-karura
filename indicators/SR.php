<?php
/*
 * SR.php
 * ------
 * Support/resistance implementation.
 *
 * Copyright (c) 2021 Thijs van der Woude
 */

/*
 * source: https://www.babypips.com/learn/forex/how-to-calculate-pivot-points
 * also interesting: https://www.babypips.com/tools/pivot-point-calculator
 */

function calcPP(float $high, float $low, float $close) {
	return ($high + $low + $close) / 3;
}

function calcR1(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return (calcPP($high, $low, $close) * 2) - $low;
		case "fib":
			return calcPP($high, $low, $close) + (($high - $low) * 0.382);
	}
}

function calcR2(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return calcPP($high, $low, $close) + ($high - $low);
		case "fib":
			return calcPP($high, $low, $close) + (($high - $low) * 0.618);
	}
}

function calcR3(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return $high + 2 * (calcPP($high, $low, $close) - $low);
		case "fib":
			return calcPP($high, $low, $close) + ($high - $low);
	}
}

function calcS1(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return (calcPP($high, $low, $close) * 2) - $high;
		case "fib":
			return calcPP($high, $low, $close) - (($high - $low) * 0.382);
	}
}

function calcS2(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return calcPP($high, $low, $close) - ($high - $low);
		case "fib":
			return calcPP($high, $low, $close) - (($high - $low) * 0.618);
	}
}

function calcS3(float $high, float $low, float $close, string $marketStateFormula) {
	switch ($marketStateFormula) {
		case "floor":
			return $low - 2 * ($high - calcPP($high, $low, $close));
		case "fib":
			return calcPP($high, $low, $close) - (($high - $low));
	}
}

?>
