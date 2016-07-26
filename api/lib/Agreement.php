<?php
/**
 * @file
 * Agreement -class for maintenance agreements.
 */


class Agreement extends Intra_Object {
	protected $dbTable = 'Agreement';
	protected $dbPrefix = 'ag';

	/**
	 * @name Status Codes
	 * Agremeent status codes. Should reflect those in Codes -tables.
	 * @see Codes
	 * @see Agreement::$ag_status
	 * @{
	 */
	const STATUS_VALID = 0; ///< Agreement is valid.
	const STATUS_OPEN = 3;  ///< Agreement is open.

	/**
	 * @}
	 */
	/**
	 * @name Database Schema
	 * @{
	 */
	public $ag_id;			///< Primary key.
	public $se_c_id;		///< End customer id: \ref Company::$c_id.
	public $ag_nr;			///< Agreement number, allocated from agreement_pool -table.
	public $ag_date1;
	public $ag_date2;
	public $ag_date3;
	public $ag_duration;
	public $ag_fee;			///< Yearly fee.
	public $ag_currency;	///< Currency. Should be EUR. See \see Invoice_Currencys
	public $ag_vat_pros;	///< Value added tax (23% or 0%)
	public $ag_rem;
	public $ag_file;
	public $ag_dealer_c_id;	///< Distributor id. \ref Company::$c_id.
	public $ag_check_terms;
	public $ag_order_nr;	///< Purchase order number.
	public $ag_order_date;	///< Purchase order date.
	public $ag_rem2;
	public $x_file2;

	/**
	 * Agreement status code.
	 * @see Codes::$cd_value
	 * @see Agreement::STATUS_OPEN
	 * @see Agreement::STATUS_VALID
	 */
	public $ag_status;
	public $ag_chgby;		///< Changed by \ref Person::$p_user
	public $ag_chgdate;		///< Modification date.

	/**
	 * @}
	 */

	/**
	 * @copydoc Intra_Object::load()
	 */
	public function &load($param=null) {
		return parent::load($param, 'Agreement');
	}

	/**
	 * Return Agreement end-customer.
	 * @return Company
	 * @see Agreement::$se_c_id
	 */
	public function customer() {
		return Company::load($this->get('se_c_id'));
	}
}
