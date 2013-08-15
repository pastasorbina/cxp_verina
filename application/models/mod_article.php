<?php

class Mod_article extends MY_Model {

  var $table_name = 'article';
  var $_table_name = 'article';
  var $id_field = 'a_id';
  var $entry_field = 'a_entry';
  var $stamp_field = 'a_stamp';
  var $status_field = 'a_status';
  var $order_by = 'a_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}



}

?>
