<?php
class Intra_View_Microformat_Invoice extends Intra_View_Microformat {

	protected $_accessors = array(
		'c_id'				=> 'billing',
		'p_id'				=> 'getRecipient',
		'in_nr'				=> 'getNr',
		'in_type'			=> 'getType',
		'in_invoice_date'	=> 'getInvoiceDate',
		'in_order_date'		=> 'getOrderDate',
		'in_payment_date'	=> 'getPaymentDate',
		'in_due_date'		=> 'getDueDate',
		'in_fee'			=> 'getFee',
		'in_vat_pros'		=> 'getVatPros',
		'in_receipt_email'	=> 'getRecipientEmail',
		'x_file'			=> 'getFileUrl',
		'in_rem'			=> 'getRemarks',
		'in_chgby'			=> 'getChangedBy',
		'in_chgdate'		=> 'getChangeDate'
	);

	public function getNr() {
		$nr = $this->getReference()->get('in_nr');
		$tag = Intra_View_Microformat_Tag::factory($nr, 'a');

		$url = intra_api_url($this->getReference());
		$tag->setAttribute('href', url($url));
		$tag->addClass('in_nr');
		return $tag;
	}

	public function getType() {
		$code = Codes::getCode('IN_TYPE', $this->getReference()->get('in_type'));

		$tag = Intra_View_Microformat_Tag_Tag::factory(t($code->get('cd_name')));
		$tag->addClass('invoice-type-'.$code->get('cd_value'));

		return $tag;
	}

	public function getInvoiceDate() {
		$datetime = $this->getReference()->get('in_invoice_date');
		$tag = Intra_View_Microformat_Tag_Time::factory($datetime)->addClass('in_invoice_date');
		return $tag;
	}

	public function getOrderDate() {
		$datetime = $this->getReference()->get('in_order_date');
		$tag = Intra_View_Microformat_Tag_Time::factory($datetime)->addClass('in_order_date');
		return $tag;
	}

	public function getPaymentDate() {
		$paid = $this->getReference()->get('in_payment_date');
		if(!$paid) {
			$due = strtotime($this->getReference()->get('in_due_date'));
			
			if(!$due || $due < time()) {
				$msg = '<span class="ui-icon ui-icon-alert"> </span>';
			} else {
				$msg = '';
			}
			$msg .= t('Unpaid');
			$tag = Intra_View_Microformat_Tag::factory($msg, 'span', false);
			$tag->addClass('invoice-unpaid');
		} else {
			$tag = Intra_View_Microformat_Tag_Time::factory($paid);
		}

		$tag->addClass('in_payment_date');
		return $tag;
	}

	public function getDueDate() {
		$datetime = $this->getReference()->get('in_due_date');
		$tag = Intra_View_Microformat_Tag_Time::factory($datetime)->addClass('in_due_date');
		return $tag;
	}

	public function getFee() {
		$fee = $this->getReference()->get('in_fee');
		$currency = $this->getReference()->get('in_currency');
		$rate = $this->getReference()->get('in_rate');
		$rate = ($rate) ? $rate : 1;

		$vat =  $this->getReference()->get('in_vat_pros');

		$symbol = '';
		$decimals = 2;

		if(module_exists('currency_api')) {
			$codes = currency_api_get_currencies();
			if($codes[$currency]) {
				$symbol = $codes[$currency]['symbol'];
				$decimals = $codes[$currency]['decimals'];
			}
		}

		$fee += $fee * ($vat / 100);

		if(module_exists('format_number')) {
			$feeView = format_number($fee, $decimals);
		} else {
			$feeView = number_format($fee, $decimals);
		}


		$view = t('!symbol!amount !code', array(
			'!symbol'	=> $symbol,
			'!amount'	=> $feeView,
			'!code'		=> $currency
		));
		$tag = $this->tag('in_fee',$view)->addClass('money');

		$tag->setAttribute('title', $fee / $rate);
		return $tag;
	}

	public function getFileUrl() {
		$tag = Intra_View_Microformat_Tag::factory($this->getReference()->get('x_file'), 'a');
		$tag->setAttribute('href', url(intra_api_url($this->getReference()).'/file'));
		$tag->addClass('x_file');
		return $tag;
	}

	public function getVatPros() {

		if(module_exists('format_number')) {
			$pros = format_number($this->getReference()->get('in_vat_pros'));
		} else {
			$pros = number_format($this->getReference()->get('in_vat_pros'));
		}
		return $this->tag('in_vat_pros', t('@pros %', array('@pros' => $pros)));
	}

	public function billing() {
		return Intra_View::factory($this->getReference()->billing()->current());
	}

	public function customer() {
		$company = $this->getReference()->customer()->current();
		if($company)
			return Intra_View::factory($company);
		return null;
	}

	public function getRecipient() {
		$person = $this->getReference()->get('p_id');
		if($person)
			return Intra_View::factory(Person::load($person));
		return null;
	}

	public function getRecipientEmail() {
		$email = $this->getReference()->get('in_receipt_email');
		if($email) {
			$tag = Intra_View_Microformat_Tag::factory($email, 'a');
			$tag->setAttribute('href', 'mailto:'.$email);
			$tag->addClass('in_receipt_email');
		}
		return $tag;
	}

	public function getRemarks() {
		$comment = $this->getReference()->get('in_rem');
		$comment = Intra_CMS()->filter($comment);
//		$comment = html_entity_decode($comment, ENT_COMPAT, 'utf-8');

		$tag = Intra_View_Microformat_Tag::factory($comment, 'div', false);
		$tag->addClass('in_rem');
		return $tag;
	}

	public function getChangedBy() {
		$user = $this->getReference()->get('in_chgby');
		if($user) {
			$person = Person::load(array('p_user' => $user))->current();
			if($person)
				return Intra_View::factory($person);
		}
	}

	public function getChangeDate() {
		$datetime = $this->getReference()->get('in_chgdate');
		$tag = Intra_View_Microformat_Tag_Time::factory($datetime)->addClass('in_chgdate');
		return $tag;
	}

	public function __toString() {

		$nr = $this->getReference()->get('in_nr');
		$viewNr = $this->get('in_nr')->__toString();

		$class[] = 'invoice';

		if($this->getReference()->get('visible') != Intra_Object::VISIBLE
			|| $this->getReference()->get('in_type') == Invoice::TYPE_REJECTED) {
			$tag = 'del';
		} elseif(!$this->getReference()->get('loadedFromDb')) {
			$tag = 'ins';
		} else {
			$tag = 'span';
		}

		$paid = $this->getReference()->get('in_payment_date');
		if(!$paid) {
			$class[] = 'ui-state-highlight';
			$class[] = 'invoice-unpaid';

			// Add icon
			$due = strtotime($this->getReference()->get('in_due_date'));
			if(!$due || $due < time()) {
				$viewNr = '<span class="ui-icon ui-icon-alert"></span>'.$viewNr;
			} else {
				$viewNr = '<span class="ui-icon ui-icon-notice"></span>'.$viewNr;
			}
		}

		$company = Company::load($this->getReference()->get('c_id'));
		$title = t('Invoice @nr for @company', array(
			'@nr' => $nr,
			'@company' => $company->get('c_cname')
		));
		return '<'.$tag.' class="'.implode(' ', $class).'" data-uid="'.$this->getUid().'" title="'.$title.'">'.$viewNr.'</'.$tag.'>';
	}
}
