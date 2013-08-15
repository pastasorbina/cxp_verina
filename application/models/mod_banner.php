<?php

class Mod_banner extends MY_Model {

  var $table_name = 'banner';
  var $_table_name = 'banner';
  var $id_field = 'b_id';
  var $entry_field = 'b_entry';
  var $stamp_field = 'b_stamp';
  var $status_field = 'b_status';
  var $order_by = 'b_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}



}

?>
