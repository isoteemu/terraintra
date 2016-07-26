<?php

function _intra_activity_lataus20($contact) {
	
	$emails = $contact->getEmails()->each()->get('pe_email')->getChildren();

	if(!count($emails)) return array();

    $db_str = variable_get('intra_activity_db', 20);
	$db = MDB2::Connect($db_str);

	$db->query("SET NAMES 'utf8'");

	if(Pear::isError($db)) {
		watchdog('intra_activity', 'Could not connect to lataus20 database. Error: '.$db->getMessage(), NULL, WATCHDOG_ERROR);
		return array();
	}

	$emails = array_map(array(&$db, 'quote'), $emails);
	$_emails = implode(',', $emails);

	$limit = variable_get('intra_activity_limit', 20);
	$age = variable_get('intra_activity_limit_age', 15768000);


	$res = $db->query("
		SELECT
			*,
			UNIX_TIMESTAMP(`time`) AS `date`
		FROM
			`terra_contact`
		WHERE
			`email` IN( $_emails )
		AND
			`email_confirmed` = 1
		AND
			`time` > FROM_UNIXTIME(UNIX_TIMESTAMP() - $age)
		ORDER BY `time` DESC
		LIMIT 0, $limit");

	if(Pear::isError($res)) {
		watchdog('intra_activity', 'Could not query lataus20 database. Error: '.$res->getMessage(), NULL, WATCHDOG_ERROR);
		return array();
	}

	while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {

		$id = $db->quote($row['id']);
		$bodyRes = $db->query("
			SELECT
				`application`,
				`cad`
			FROM
				`terra_ladatut`
			WHERE
				`id` = $id
		");

		$body	= array();
		while($bodyRow = $bodyRes->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$body[] = t('%app for %cad', array(
				'%app' => $bodyRow['application'],
				'%cad' => $bodyRow['cad']
			));
		}

		$msg = implode(", ", $body);
		$msg = check_markup($msg, FILTER_FORMAT_DEFAULT, FALSE);

		$r[$row['id']] = new Intra_Activity_Event_Lataus20(array(
			'title' => $row['company'],
			'contact' => null,
			'date' => $row['date'],
			'body' => $msg
		));
	}

	$db->disconnect();

	return $r;

}
