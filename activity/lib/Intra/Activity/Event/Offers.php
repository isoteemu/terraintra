<?php
/**
 * Webfrom event.
 */

class Intra_Activity_Event_Offers implements Intra_Activity_Event, Intra_Activity_TypedEvent {

	protected $title;
	protected $body;
	protected $date;
	protected $contact;
	protected $direction = Intra_Activity_Event::EVENT_TO;

	protected $payload;

	protected $type = 'offer';

	public function __construct($row) {
		foreach($row as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function getTitle() {

		$info = $this->parseUUID($this->payload['uuid']);
		try {
			$customer = Company::load($info['customer']);
			$title = (string) intra_api_view($customer);
		} catch(Exception $e) {
			Intra_CMS()->dfb($e);

			if(module_exists('intra_search_company')) {
				$title = l(
					$this->title,
					'search/intra_search_company/'.urlencode($this->title)
				);
			} else {
				$title = theme('placeholder', $this->title);
			}
		}

		$distributor =  theme('placeholder', t('Unknown company'));

		try {
			$company = Company::load($info['distributor']);
			$distributor = (string) Intra_View::factory($company);
		} catch(Exception $e) {
			// Non-fatal errors.
			Intra_CMS()->dfb($e);
		}


		$t = t('[Offer] #%nr from !from for !title', array(
			'%nr' => $info['offerid'],
			'!from' => $distributor,
			'!title' => $title
		));
		return $t;
	}

	public function getBody() {
		return Intra_CMS()->filter($this->body);
	}

	public function getDate() {
		return $this->date;
	}

	public function getActions() {
		$links = array();
		$links[] = l(
			'Offer',
			'http://www.example.com/support/offers/'.$this->payload['uuid'],
			array('title' => t('View Offer'))
		);

		foreach($this->payload['files'] as $file) {
			$links[] = l(
				t('File'),
				'http://www.example.com/support/offers/'.$this->payload['uuid'].'/files/download/'.$file['fid'].'/'.urlencode($file['filename']),
				array('attributes' => array(
					'class' => 'file-attachment',
					'type' => $file['filemime'],
					'title' => $file['filename']
				))
			);
		}
		return $links;
	}

	public function getContact() {
		return $this->contact;
	}

	public function getDirection() {
		return $this->direction;
	}

	public function getType() {
		return $this->type;
	}

	protected function parseUUID($uuid) {
		$parts = explode('-', $uuid);

		return array(
			'customer' => hexdec($parts[0]),
			'distributor' => hexdec($parts[1]),
			'time' => hexdec($parts[2]),
			'offerid' => hexdec($parts[3])
		);
	}

}
