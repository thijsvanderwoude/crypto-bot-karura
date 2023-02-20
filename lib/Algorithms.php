<?php
/*
 * Algorithms.php
 * --------------
 * To be removed?
 */

function volumePriceProspect(array $recentTrades) {
	$askVolume = 0;
	for ($i = 0; $i < sizeof($recentTrades["asks"]); $i++) {
		$askVolume += $recentTrades["asks"][$i][1] * $recentTrades["asks"][$i][0];
	}
	
	$bidVolume = 0;
	for ($i = 0; $i < sizeof($recentTrades["bids"]); $i++) {
		$bidVolume += $recentTrades["bids"][$i][1] * $recentTrades["bids"][$i][0];
	}
	
	$percentDiff = (1 - $askVolume / $bidVolume) * 100;
	
	if ($percentDiff > 0) {
		
	} else {
		
	}
	
	return $percentDiff;
}

function mfi() {
	return 0;
}
?>
