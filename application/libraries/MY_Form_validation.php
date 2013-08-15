<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

	function __construct() {
		parent::__construct();

		$this->set_defaults();
	}

	function set_defaults() {
		$this->set_error_delimiters('<div class="red" style="margin-top:2px;">', '</div>');
	}

	/** date validation rules
	 * @
	 */
	function valid_date($date) {
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/' , $date , $matches)) {
            list($dummy,$ye,$mo,$da) = $matches;
            if (checkdate($mo , $da , $ye)) {
                return true;
            }
            else {
                $this->set_message('valid_date', 'Invalid date on %s field');
                return false;
            }
        }
        else {
            $this->set_message('valid_date', 'Invalid date on %s field');
            return false;
        }
    }



}
