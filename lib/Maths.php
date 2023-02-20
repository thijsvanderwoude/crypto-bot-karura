<?php

function calcAvgPrice(array $recentTrades) {
	$avgTotal = 0;
	for ($i = 0; $i < sizeof($recentTrades); $i++) {
		$avgTotal += $recentTrades[$i]["price"];
	}
	return $avgTotal / sizeof($recentTrades);
}
?>
