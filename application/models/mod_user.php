<?php

class Mod_user extends MY_Model {

  var $table_name = 'user';
  var $_table_name = 'user';
  var $id_field = 'u_id';
  var $entry_field = 'u_entry';
  var $stamp_field = 'u_stamp';
  var $status_field = 'u_status';
  var $order_by = 'u_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}
 


}

?>
