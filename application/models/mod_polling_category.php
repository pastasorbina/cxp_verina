<?php

class Mod_polling_category extends MY_Model {

	var $table_name = 'polling_category';
	var $id_field = 'pollc_id';
	var $entry_field = 'pollc_entry';
	var $stamp_field = 'pollc_stamp';
	var $status_field = 'pollc_status';
	var $deletion_field = 'pollc_deletion';
	var $order_by = '';
	var $order_dir = '';

    function __construct() {
        parent::__construct();
    }

}

?>
