<?php

function intra_activity_provider_amavisd_settings() {
	$form = array();
	$archive = variable_get('intra_activity_imap', 'imap://10.11.12.25/Archive/%Y');
	$amavisd = variable_get('intra_activity_amavisd_dns', 'mysqli://10.11.12.6/amavisd');

	$form['intra_activity_imap'] = array(
		'#type' => 'textfield',
		'#title' => t('Imap URL for connecting to mail archive'),
		'#description' => t('URI for imap mail server. Possible replacement values are as in php strftime() function'),
		'#default_value' => $archive
	);

	$form['intra_activity_amavisd_dns'] = array(
		'#type' => 'textfield',
		'#title' => t('Database DNS for amavisd penpal tables'),
		'#default_value' => $amavisd
	);

	return $form;
}

/**
 * Amavisd-new penpal functions
 */

/**
 * Retrieve last email discussions from Amavisd_new table.
 * @todo Database connection dns into variable
 */
function _intra_activity_amavisd(Person $contact) {
	$events = array();

	$emails = $contact->getEmails()->each()->get('pe_email')->getChildren();
	if(!count($emails)) return array();

	$dns = variable_get('intra_activity_amavisd_dns', 'mysqli://10.11.12.6/amavisd');
	$ama = MDB2::Connect($dns, array(
		'debug'		=> 2
	));

	$emails = array_map('strtolower', $emails);
	$_emails = array_map(array(&$ama, 'quote'), $emails);
	$_emails = implode(', ', $_emails);

	$limit = variable_get('intra_activity_limit', 20);
	$age = variable_get('intra_activity_limit_age', 15768000);

	$res = $ama->query("
		SELECT
			msgs.subject AS `subject`,
			msgs.message_id AS `message_id`,
			msgs.time_num AS `time`,
			sender.email AS `from`,
			recip.email AS `recip`
		FROM msgrcpt
		LEFT JOIN msgs ON msgs.mail_id=msgrcpt.mail_id
		INNER JOIN maddr AS sender ON msgs.sid=sender.id
		INNER JOIN maddr AS recip ON msgrcpt.rid=recip.id
		WHERE (
			recip.email IN( $_emails )
		OR
			sender.email IN( $_emails )
		) AND
			content LIKE 'C'
		AND
			time_num > UNIX_TIMESTAMP() - $age
		ORDER BY msgs.time_iso DESC
		LIMIT $limit");

	while($message = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {

		if(in_array(strtolower($message['from']), $emails)) {
			$is = $is = Intra_Activity_Event::EVENT_FROM;
			$who = _intra_activity_email_idx($message['recip']);
		} else {
			$is = Intra_Activity_Event::EVENT_TO;
			$who = _intra_activity_email_idx($message['from']);
		}

		if(!$who) {
			dfb('Not known contact email: '.$message['from'].' or '.$message['recip']);
			continue;
		}

		$payload = array(
			'message_id' => $message['message_id']
		);

		if(preg_match('/\bInvoice ([a-z]+[\d]+)/i', $message['subject'], $match)) {
			try {
				$invoice = Invoice::load(array('in_nr' => $match[1]));
				$payload['invoice'] = $invoice->get('id');
			} catch( Exception $e ) {
				Intra_CMS()->dfb($e);
			}

		}

		$event = new Intra_Activity_Event_Amavisd(array(
			'archive' => variable_get('intra_activity_imap', 'imap://10.11.12.25/Archive/%Y'),
			'title' => $message['subject'],
			'body' => '',
			'date' => $message['time'],
			'contact' => $who->get('p_id'),
			'direction' => $is,
			'payload' => $payload
		));
		

		$events[] = $event;

	}

	$ama->disconnect();

	return $events;
}
