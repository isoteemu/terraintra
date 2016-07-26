<?php

class Intra_Activity_Event_Amavisd implements Intra_Activity_Event, Intra_Activity_TaggedEvent {

	protected $title;
	protected $body;
	protected $date;
	protected $contact;
	protected $direction = Intra_Activity_Event::EVENT_TO;

	protected $payload;

	protected $type = 'email';

	/**
	 * Imap DSN for archive folder.
	 * Path should be a strftime() compatible string.
	 */
	public $archive = '';

	protected static $imapManager = array();

	/**
	 * Thunderbird flags to visible name
	 */
	public $flagMap = array(
		'$label1'	=> 'Important',
		'$label2'	=> 'Work',
		'$label3'	=> 'Personal',
		'$label4'	=> 'To Do',
		'$label5'	=> 'Later',

//		'\Seen'		=> 'Seen',
		'\Answered'	=> 'Answered',
		'$MDNSent'	=> 'MDN',
	);

	public function __construct($row) {

		foreach($row as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function getTitle() {
		return theme('placeholder', $this->title);
	}

	public function getBody() {
		$this->lazyLoad();
		return $this->body;
	}

	public function getDate() {
		return $this->date;
	}

	public function getActions() {
		$links = array();

		if($this->payload['uid']) {
			$links[] = l(
				t('Download'),
				sprintf('https://www.example.com/horde/services/download/?module=imp&actionID=save_message&mailbox=%s&index=%s&fn=%s',
					urlencode($this->payload['folder']),
					urlencode($this->payload['uid']),
					urlencode($this->title).'.eml'
				)
			);

			// If imap protocol is allowed, generate imap link
			$protocols = variable_get('filter_allowed_protocols', array());
			if(in_array('imap', $protocols)) {
				global $user;
				$uri = parse_url($this->archive);

				// real rfc documented format.
				$links[] = l(
					t('Imap'),
					sprintf(
						'imap://%s%s/%s?UID=%d',
						($user->name) ? "{$user->name}@" : '',
						$uri['host'],
						$this->payload['folder'],
						$this->payload['uid']
					),
					array('attributes' => array(
						'type' => 'message/rfc822'
					))
				);

				// Thunderbird requires something else...
				// imap://testaaja@example.com:143/fetch>UID>/Archive/2010>2556
				$links[] = l(
					t('Thunderbird'),
					sprintf(
						'imap://%s%s/fetch>UID>%s>%d',
						($user->name) ? "{$user->name}@" : '',
						$uri['host'],
						$this->payload['folder'],
						$this->payload['uid']
					)
				);

			}

		} elseif($this->payload['message_id']) {
			$ret = url($_GET['q'], array('absolute' => true));
			$links[] = l(
				t('Horde'),
				'https://www.example.com/horde/teemu/search_by_messageid.php?messageid='.urlencode($this->payload['message_id']).'&frameset_loaded=1&return='.urlencode($ret)
			);
		}
		if($this->payload['invoice']) {
			$invoice = Invoice::load($this->payload['invoice']);
			$links[] = l(
				t('Invoice'),
				intra_api_url($invoice),
				array('attributes' => array(
					'class' => 'file-attachment',
					'title' => t('Invoice %nr', array('%nr' => $invoice->get('in_nr')))
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

	/**
	 * @todo Only accepts ascii-chars.
	 */
	public function getTags() {
		$this->lazyLoad();
		$tags = array();
		if($this->payload['flags']) {
			dfb($this->payload['flags']);
			foreach($this->payload['flags'] as $flag) {

				if(isset($this->flagMap[$flag])) {
					$tags[] = $this->flagMap[$flag];
				} elseif(!preg_match('/[^a-z_]/i', $flag)) {
					$tags[] = str_replace('_', ' ', $flag);
				}
			}
		}
		return $tags;
	}

	protected function lazyLoad() {

		if(!$this->payload['uid']) {
			$this->IMAP();

			$uri = parse_url($this->archive);
			$folder = strftime($uri['path'], $this->date);
			if($folder[0] == '/') $folder = ltrim($folder, '/');

			$cmd = $this->IMAP()->selectMailbox($folder);

			if(Pear::isError($cmd)) return;
			$id = 'TEXT "Message-Id: '.$this->payload['message_id'].'"';

			$uid = $this->IMAP()->search($id, true);

			if(Pear::isError($uid)) return;

			if(count($uid)) {
				$msg = $this->IMAP()->getMsg($uid, true);

				$mail = new Mail_mimeDecode($msg);
				$structure = $mail->decode(array(
					'include_bodies' => true,
					'decode_bodies' => true,
					'decode_headers' => false
				));

				$this->body = $this->parseMsgBody($structure);
				$this->payload['uid']    = $uid[0];
				$this->payload['folder'] = $folder;

				$flags = array();
				$imapFlags = $this->IMAP()->getFlags($uid);
				if(!Pear::isError($imapFlags)) {
					foreach($imapFlags as $flagset) {
						$flags += $flagset;
					}
					$this->payload['flags'] = array_unique($flags);
				}
			}

		}
	}

	/**
	 * TODO: Prefer HTML
	 */
	protected function parseMsgBody($msg) {

		$r = '';
		if($msg->ctype_primary == 'text' && $msg->ctype_secondary == 'html') {
			$r = $msg->body;
			if($msg->ctype_parameters['charset'])
				$r = drupal_convert_to_utf8($r, $msg->ctype_parameters['charset']);

			$r = Intra_CMS()->filter($r);
		} elseif($msg->ctype_primary == 'text' && $msg->ctype_secondary == 'plain') {
			$r = $msg->body;
			if($msg->ctype_parameters['charset'])
				$r = drupal_convert_to_utf8($r, $msg->ctype_parameters['charset']);

			$r = Intra_CMS()->filter($r);
		} elseif($msg->ctype_primary == 'multipart' && ( $msg->ctype_secondary == 'mixed' || $msg->ctype_secondary == 'alternative')) {
			foreach($msg->parts as $part) {
				$r = $this->parseMsgBody($part);
				if($r) break;
			}
		}
		return $r;
	}

	protected function &IMAP() {
		if(empty($this->archive)) {
			throw new InvalidArgumentException('Required parameter $this->archive is not defined');
		}
		if(!self::$imapManager[$this->archive]) {
			require_once('Net/IMAP.php');

			$uri = parse_url($this->archive);
			$uri = array_merge(array(
				'scheme'	=> 'imap',
				'host'		=> 'localhost',
				'port'		=> 143,
				'path'		=> 'Archive/%Y'
			), $uri);

			self::$imapManager[$this->archive] = new Net_IMAP($uri['host'], $uri['port']);
			self::$imapManager[$this->archive]->login($uri['user'], $uri['pass'], false, false);
			//$this->imap->setDebug();
		}
		return self::$imapManager[$this->archive];
	}

}
