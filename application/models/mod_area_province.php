<?php

class Mod_area_province extends MY_Model {

  var $table_name = 'area_province';
  var $id_field = 'ap_id';
  var $entry_field = 'ap_entry';
  var $stamp_field = 'ap_stamp';
  var $status_field = 'ap_status';
  var $order_by = 'ap_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}



}

?>
