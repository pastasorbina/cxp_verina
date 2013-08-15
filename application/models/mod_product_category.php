<?php

class Mod_product_category extends MY_Model {

	var $table_name = 'product_category';
	var $id_field = 'pc_id';
	var $entry_field = 'pc_entry';
	var $stamp_field = 'pc_stamp';
	var $status_field = 'pc_status';
	var $deletion_field = 'pc_deletion';
	var $order_by = '';
	var $order_dir = '';

    function __construct() {
        parent::__construct();
    }

}

?>
