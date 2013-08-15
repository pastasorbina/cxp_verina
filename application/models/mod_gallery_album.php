<?php

class Mod_gallery_album extends MY_Model {

  var $table_name = 'gallery_album';
  var $_table_name = 'gallery_album';
  var $id_field = 'ga_id';
  var $entry_field = 'ga_entry';
  var $stamp_field = 'ga_stamp';
  var $status_field = 'ga_status';
  var $order_by = 'ga_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}

	 

}

?>
