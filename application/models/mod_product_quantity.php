<?php

class Mod_product_quantity extends MY_Model {

	var $table_name = 'product_quantity';
	var $id_field = 'pq_id';
	var $entry_field = 'pq_entry';
	var $stamp_field = 'pq_stamp';
	var $status_field = 'pq_status';
	var $deletion_field = 'pq_deletion';
	var $order_by = 'pq_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}

	function return_stock($pq_id, $quantity) {
		
		return($ok);
	}


}
