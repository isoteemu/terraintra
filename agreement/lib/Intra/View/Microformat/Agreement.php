<?php
/**
 * Maintenance agreement view object
 */

class Intra_View_Microformat_Agreement extends Intra_View_Microformat {

	protected $_accessors = array(
		'ag_nr'  => 'getAgreement',
		'ag_status' => 'getStatus'
	);

	public function getAgreement() {
		$nr = $this->getReference()->get('ag_nr');
		$tag = Intra_View_Microformat_Tag::factory($nr, 'a');

		$url = intra_api_url($this->getReference());
		$tag->setAttribute('href', url($url));

		return $tag;
	}

	public function getStatus() {
		static $cache;

		$status = $this->getReference()->get('ag_status');
		$code = Codes::getCode('AG_STATUS', $status);

		$tag = $this->tag('ag-status-'.$code->get('cd_value'), t($code->get('cd_name')));

		if($status == Agreement::STATUS_VALID) {
			$expires = strtotime($this->getReference()->get('ag_date3'));
			$now = time();

			if($now > $expires+86400) {
				$tag[0] = t('Expired');
				$tag->setAttribute('title', t('Expired: !date', array(
					'!date' => format_date($expires),
					'class' => 'warning'
				)));

			} elseif($now > $expires-2592000) {
				$tag->setAttribute('title', t('Expires soon: !date', array(
					'!date' => format_date($expires),
					'class' => 'warning'
				)));
			}
		}

		return $tag;

	}

	public function __toString() {

		$status	= $this->asText()->getStatus();
		$title	= check_plain((string) $status);

		$ag		= (string) $this->getAgreement();
		$class	= array();
		$class[]= 'agreement';
		$class[]= 'agreement-nr';

		switch($this->getReference()->get('ag_status')) {
			case 1:	// Terminated
			case 6: // Removed
				$tag = 'del';
				break;
			default :
				$tag = 'span';
				break;
		}

		return '<'.$tag.' class="'.implode(', ', $class).'" data-uid="'.$this->getUid().'" title="'.$title.'">'.$ag.'</'.$tag.'>';
	}
}

