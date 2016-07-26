<?php
/**
 * @file
 * Common ranking methods.
 */

/**
 * Calculate ranking by using levenshtein.
 */
function intra_search_api_rank_levenshtein($results, $keywords, $scope) {
	$range = array();
	foreach($results as $id => $entry) {
		$range[$id] = levenshtein($keywords, $entry->get($scope));
	}

	$weights = _intra_search_api_scale_rank($range, true);

	return $weights;
}

/**
 * TODO: range and order doens't allways match back
 */
function intra_search_api_rank_alpahabet($results, $keywords, $scope) {
	foreach($results as $id => $entry) {
		$order[$id] = $entry->get($scope);
	}

	$c = count($order);

	$step = 1 / ($c-1);
	arsort($order, SORT_STRING);

	$range = range(0, 1, $step);

	return array_combine(
		array_keys($order),
		$range
	);

}

/**
 * Converts scale into 0 to 1.
 * @param $descending Bool
 *   Sort results by descending logic if true. asscending if false
 */
function _intra_search_api_scale_rank($range, $descending=false) {

	$weights = array();
	$worst = max($range);
	$best = min($range);

	if($best == $worst) {
		// no point to continue, good as nothing.
		return $weights;
	}

	$diff = $worst - $best;

	foreach($range as $id => $match) {
		// Calculate match, where best gets 1 point and worst 0
		$weights[$id] = ($diff - ( $worst - $match ) ) / $diff;
		if($descending)
			$weights[$id] = 1 - $weights[$id];
	}


	return $weights;
}