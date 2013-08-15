<?php

class Mod_shipping_method extends MY_Model {

	var $table_name = 'shipping_method';
	var $_table_name = 'shipping_method';
	var $id_field = 'sm_id';
	var $entry_field = 'sm_entry';
	var $stamp_field = 'sm_stamp';
	var $status_field = 'sm_status';
	var $deletion_field = 'sm_deletion';
	var $order_by = 'sm_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}


}

?>
