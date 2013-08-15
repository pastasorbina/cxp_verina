<?php

class Mod_product_type extends MY_Model {

	var $table_name = 'product_type';
	var $id_field = 'pt_id';
	var $entry_field = 'pt_entry';
	var $stamp_field = 'pt_stamp';
	var $status_field = 'pt_status';
	var $deletion_field = 'pt_deletion';
	var $order_by = '';
	var $order_dir = '';

    function __construct() {
        parent::__construct();
    }

}

?>
