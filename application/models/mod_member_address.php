<?php

class Mod_member_address extends MY_Model {

	var $table_name = 'member_address';
	var $_table_name = 'member_address';
	var $id_field = 'madr_id';
	var $entry_field = 'madr_entry';
	var $stamp_field = 'madr_stamp';
	var $status_field = 'madr_status';
	var $deletion_field = 'madr_deletion';
	var $order_by = 'madr_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}


}

?>
