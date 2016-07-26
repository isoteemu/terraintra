<?php
/**
 * @file
 * Codes_Helper class.
 */
/**
 * ORM Representation of Code -table.
 */
class Codes_Helper extends Intra_Object {
	protected $dbTable = 'Code';
	protected $dbPrefix = 'cd';

	/**
	 * @name Database Schema
	 " @{
	 */
	public $cd_id;		///< Primary key.
	public $cd_code;	///< Code type -code.
	public $cd_value;	///< Code value.
	public $cd_name;	///< Code key/name.

	/**
	 * @}
	 */
	public function &load($param) {
		return parent::load($param, 'Codes_Helper');
	}
}
