<?php

class Mod_payment_method extends MY_Model {

	var $table_name = 'payment_method';
	var $_table_name = 'payment_method';
	var $id_field = 'pm_id';
	var $entry_field = 'pm_entry';
	var $stamp_field = 'pm_stamp';
	var $status_field = 'pm_status';
	var $deletion_field = 'pm_deletion';
	var $order_by = 'pm_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}


}

?>
