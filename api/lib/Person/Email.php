<?php
/**
 * Person email entry.
 * @var Person_Email->pe_primary
 *   Pseudo-value, for defining if email is same as in Person->p_email
 * @var pe_optout
 *   Is email opted-out from future correspondance
 * @var pe_dead
 *   Is email dead
 */
class Person_Email extends Intra_Object {

	/**
	 * @name Subscription flags.
	 * @{
	 */
	const OPTIN  = 0;			///< Can be sent promotions.
	const OPTOUT = 1;			///< Don't send promotions.
	/**
	 * @}
	 */

	/**
	 * @name Status flags.
	 * @{
	 */
	const INUSE  = 0;			///< Email is active
	const DEAD	 = 1;			///< Email is inactive.
	/**
	 * @}
	 */

	/**
	 * @name Database Schema
	 * @{
	 */
	public $pe_id;				///< Primary key.
	public $pe_primary	= 0;	///< Is email primary. Collection should have only one primary.
	public $pe_optout	= Person_Email::OPTIN;
	public $pe_dead		= Person_Email::INUSE;
	/**
	 * @}
	 */

	protected $dbTable = 'Person_email';
	protected $dbPrefix = 'pe';

	public function &load($param) {
		return parent::load($param, 'Person_Email');
	}

	/**
	 * Convert email object into plain email string.
	 * Doesn't use view object.
	 * @return String Email
	 */
	public function __toString() {
		return (string) $this->get('pe_email');
	}
}
