<?php

function _intra_activity_webform($contact) {

	$emails = $contact->getEmails()->each()->get('pe_email')->getChildren();
	if(!count($emails)) return array();

    $db_str = variable_get('intra_activity_db', 20);
	$db = MDB2::Connect($db_str);

	$db->query("SET NAMES 'utf8'");

	if(Pear::isError($db)) {
		watchdog('intra_activity', 'Could not connect to webform database. Error: '.$db->getMessage(), NULL, WATCHDOG_ERROR);
		return array();
	}

	$emails = array_map(array(&$db, 'quote'), $emails);
	$_emails = implode(',', $emails);

	$limit = variable_get('intra_activity_limit', 20);
	$age = variable_get('intra_activity_limit_age', 15768000);


	$res = $db->query("
		SELECT 
			n.title AS title,
			s.submitted AS date,
			n.nid AS nid,
			s.sid AS sid,
			d.data AS frm,
			w.email AS rcpt

		FROM `webform_component` AS wc
		LEFT JOIN node AS n ON
			n.nid=wc.nid
		LEFT JOIN webform_submitted_data AS d ON
			d.cid=wc.cid
		LEFT JOIN webform_submissions AS s ON
			s.sid=d.sid
		LEFT JOIN webform AS w ON
			w.nid=wc.nid
		WHERE
			s.sid IS NOT NULL
		AND
			n.nid IS NOT NULL
		AND
			wc.type = 'email'
		AND
			d.data IN ( $_emails )
		AND
			s.submitted > UNIX_TIMESTAMP() - $age
		GROUP BY s.sid
		ORDER BY s.submitted DESC
		LIMIT 0, $limit");


	if(Pear::isError($res)) {
		watchdog('intra_activity', 'Could not query webform database. Error: '.$res->getMessage(), NULL, WATCHDOG_ERROR);
		return array();
	}


	while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		$user = _intra_activity_email_idx($row['rcpt']);

		if(!$user) continue;

		$r[$row['sid']] = new Intra_Activity_Event_Webform(array(
			'title' => $row['title'],
			'contact' => $user->get('p_id'),
			'date' => $row['date'],
			'payload' => array(
				'nid' => $row['nid'],
				'sid' => $row['sid'],
			)
		));
	}

	$db->disconnect();

	return $r;

}
