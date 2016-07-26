<?php

/**
 * @todo Handle offer requests (reversed direction)
 */
function _intra_activity_offers($contact) {

	if(!$contact->filter(array('p_class' => 'Contact')))
		return array();

	$c_id = $contact->get('c_id');

    $db_str = variable_get('intra_activity_db', 20);
	$db = MDB2::Connect($db_str);

	if(Pear::isError($db)) {
		watchdog('intra_activity', 'Could not connect to offers database. Error: '.$db->getMessage(), NULL, WATCHDOG_ERROR);
		return array();
	}
	$db->query("SET NAMES 'utf8'");

	$uuid = sprintf('%08x', $c_id);
	$limit = variable_get('intra_activity_limit', 20);
	$age = variable_get('intra_activity_limit_age', 15768000);

	$sql = "
		SELECT
			`uuid`,
			`values`,
			`time`
		FROM `terra_offers`
		WHERE
			`uuid` LIKE '$uuid-%-%-%'
		AND
			`time` > UNIX_TIMESTAMP() - $age
		ORDER BY `expire` DESC, `time` DESC
		LIMIT 0, $limit
	";

	$res = $db->query($sql);

	$r = array();
	Intra_CMS()->dfb($res->numRows(), "$uuid-%-%-%");


	while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {


		$data = unserialize($row['values']);

		$email = $data['from'];
		if(preg_match('/<([^@]+@[^>]+)>/', $email, $match))
			$email = $match[1];

		$user = _intra_activity_email_idx($email);
		if(!$user) continue;

		$fid = implode(',', $data['files']); 
		$fres = $db->query("
			SELECT `fid`, `filename`, `description`, `filemime`
			FROM `terra_offers_files`
			WHERE `fid` IN ($fid)
		");

		$files = array();
		while($file = $fres->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$files[$file['fid']] = $file;
		}

		$r[$row['uuid']] = new Intra_Activity_Event_Offers(array(
			'title' => $data['C_CNAME'],
			'body' => $data['message'],
			'contact' => $user->get('p_id'),
			'date' => $row['time'],
			'direction' =>  Intra_Activity_Event::EVENT_TO,
			'payload' => array(
				'uuid' => $row['uuid'],
				'files' => $files
			)
		));
	}

	return $r;
}
