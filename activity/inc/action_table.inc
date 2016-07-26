<?php

function _intra_activity_actiontable($contact) {
	$c_id = $contact->get('c_id');
	// Detect classes, and map

	$ac_types = _intra_activity_actiontable_actypes($contact->get('p_class'));

	if(!count($ac_types)) {
		return array();
	}

	$limit = variable_get('intra_activity_limit', 20);
	$age = variable_get('intra_activity_limit_age', 15768000);

	$types = "'".implode("','", $ac_types)."'";

	$res = $contact->db()->query("
		SELECT
			`ac_id`,
			`ac_date1`,
			`ac_type`,
			UNIX_TIMESTAMP(`ac_date1`) AS `time`,
			`ac_title`,
			`ac_memo`,
			`ac_chgby`,
			`x_file`,
			'x_var',
			'x_id'
		FROM `Action`
		WHERE
			`c_id` = $c_id
		AND
			`ac_date1` >= FROM_UNIXTIME(UNIX_TIMESTAMP() - $age)
		AND
			`ac_type` IN ($types)
		ORDER BY `ac_date1` DESC
		LIMIT 0, $limit");

	$r = array();

	while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {

		$user = _intra_activity_actiontable_idx($row['ac_chgby']);
		if(!$user) continue;

		$r[$row['ac_id']] = new Intra_Activity_Event_ActionTable(array(
			'title' => $row['ac_title'],
			'body' => $row['ac_memo'],
			'contact' => $user->get('p_id'),
			'date' => $row['time'],
			'payload' => array(
				'x_file' => $row['x_file'],
				'x_var' => $row['x_var'],
				'x_id' => $row['x_id']
			)
		));
	}

	return $r;
}

/**
 * Map person classes into action types
 */
function _intra_activity_actiontable_actypes($classes) {
	$types = array();
	foreach($classes as $class) {
		switch($class) {
			case 'Manager' :
				$types[] = 6; // Distributor agreement
				break;
			case 'Contact' :
				$types[] = 2; // Neuvottelu
				$types[] = 3; // Offer request
				$types[] = 4; // Offer
				$types[] = 7; // Maintence Agreement
				$types[] = 10; // Licensing
				$types[] = 12; // Rekisteröintipyyntö
				break;
			case 'Account' :
				$types[] = 1; // Reklamaatio
				$types[] = 5; // Order
				$types[] = 8; // Invoice
				$types[] = 9; // Request to pay
				$types[] = 15; // Removed invoice
				break;
			case 'User' :
				$types[] = 11; // Mass email
				break;
		}
	}
	return array_unique($types);
}

function _intra_activity_actiontable_idx($user) {
	static $people = array();

	if(!isset($people[$user])) {
		$person = Person::load(array('%p_user' => $user));
		if($person instanceof Person) {
			$people[$user] = $person;
		} elseif($person && $person->count()) {
			$people[$user] = $person->current();
		} else {
			dfb(" Not found $user");
			$people[$user] = false;
		}
	}

	return $people[$user];
	strtolower($user);
}