<?php

class Mod_article_category extends MY_Model {

  var $table_name = 'article_category';
  var $_table_name = 'article_category';
  var $id_field = 'ac_id';
  var $entry_field = 'ac_entry';
  var $stamp_field = 'ac_stamp';
  var $status_field = 'ac_status';
  var $order_by = 'ac_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}



}

?>
