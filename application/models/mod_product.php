<?php

class Mod_product extends MY_Model {

	var $table_name = 'product';
	var $id_field = 'p_id';
	var $entry_field = 'p_entry';
	var $stamp_field = 'p_stamp';
	var $status_field = 'p_status';
	var $deletion_field = 'p_deletion';
	var $order_by = 'p_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
		$this->load->model('mod_brand');
	}

	function _set_default_join() {
		$join[] = array('brand', 'brand.br_id = product.br_id', 'left');
		$join[] = array('product_category', 'product_category.pc_id = product.pc_id', 'left');
		return $join;
	}

	function get_featured_product() {

		$this->db->join('promo' , 'promo.pr_id = promo_detail.pr_id' , 'left');
		$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
		$this->db->join('brand' , 'brand.br_id = product.br_id' , 'left');
		$this->db->join('product_category' , 'product_category.pc_id = product.pc_id' , 'left');
		$this->db->where('pr_status' , 'Active');
		$this->db->where('product.p_is_featured' , 'Yes');
		$this->db->where('product.p_status' , 'Active');
		$this->db->order_by('product.p_order' , 'ASC');
		$res = $this->db->get('promo_detail');
		$featured_product = $res->result_array();
		return $featured_product;
	}

	function get_new_arrivals($limit=10, $offset=0){
		$this->db->join('brand' , 'brand.br_id = product.br_id' , 'left');
		$this->db->join('product_category' , 'product_category.pc_id = product.pc_id' , 'left');
		$this->db->where('p_status' , 'Active');
		$this->db->where('product.p_status' , 'Active');
		$this->db->order_by('product.p_entry' , 'DESC');
		$this->db->limit($limit, $offset);
		$res = $this->db->get('product');
		$result = $res->result_array();
		return $result;
	}


	function get_product_by_id($p_id=0) {
		$this->db->join('brand' , 'brand.br_id = product.br_id' , 'left');
		$this->db->join('product_category' , 'product_category.pc_id = product.pc_id' , 'left');
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product');
		$result = $res->row_array();
		return $result;
	}

	//function stock_in($config=array() ) {
	//	if(empty($config)){ return FALSE; }
	//	$ok = FALSE;
	//	$action = isset($config['action']) ? $config['action'] : 'stock_out';
	//	$id = isset($config['id']) ? $config['id'] : 0;
	//	$change = isset($config['change']) ? $config['change'] : 0;
	//
	//	$lock = db_get_lock('stock_change');
	//	if($lock == 1) {
	//		//start
	//		$this->db->trans_start();
	//
	//		//getstock before
	//		$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
	//		$res = $this->db->query($query_string);
	//		$config['before'] = $res->row_array();
	//
	//		//update product quantity
	//		$query_string = "UPDATE product_quantity SET pq_quantity = pq_quantity+$change WHERE pq_id = $id ;";
	//		$this->db->query($query_string);
	//		$affected_rows = $this->db->affected_rows();
	//
	//		if($affected_rows > 0) {
	//			//get stock after
	//			$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
	//			$res = $this->db->query($query_string);
	//			$config['after'] = $res->row_array();
	//			//do stock history
	//			$this->stock_history($config);
	//		}
	//		//complete
	//		$this->db->trans_complete();
	//		$ok = $this->db->trans_status();
	//	}
	//	db_release_lock('stock_change');
	//	return $ok;
	//}
	//
	//function stock_out($config=array() ) {
	//	if(empty($config)){ return FALSE; }
	//	$ok = FALSE;
	//	$action = isset($config['action']) ? $config['action'] : 'stock_out';
	//	$id = isset($config['id']) ? $config['id'] : 0;
	//	$change = isset($config['change']) ? $config['change'] : 0;
	//
	//	$lock = db_get_lock('stock_change');
	//	// LOCK TABLES product_quantity , a ,b  ,c , d
	//	if($lock == 1) {
	//		//start
	//		$this->db->trans_start();
	//
	//		//getstock before
	//		$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
	//		$res = $this->db->query($query_string);
	//		$config['before'] = $res->row_array();
	//
	//		//update product quantity
	//		$query_string = "
	//			UPDATE product_quantity SET pq_quantity = pq_quantity-$change
	//			WHERE pq_id = $id AND pq_quantity > $change
	//			;";
	//		$this->db->query($query_string);
	//		$affected_rows = $this->db->affected_rows();
	//
	//		if($affected_rows > 0) {
	//			//get stock after
	//			$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
	//			$res = $this->db->query($query_string);
	//			$config['after'] = $res->row_array();
	//			//do stock history
	//			$this->stock_history($config);
	//		}
	//		//complete
	//		$this->db->trans_complete();
	//		$ok = $this->db->trans_status();
	//	}
	//	db_release_lock('stock_change');
	//	return $ok;
	//}
	//
	//function stock_history($config=array() ) {
	//	//breakdown config
	//	$action = isset($config['action']) ? $config['action'] : '';
	//	$id = isset($config['id']) ? $config['id'] : 0;
	//	$change = isset($config['change']) ? $config['change'] : 0;
	//	$note = isset($config['note']) ? $config['note'] : '';
	//	$is_auto = isset($config['is_auto']) ? $config['is_auto'] : 'No';
	//	$u_id = isset($config['u_id']) ? $config['u_id'] : 0;
	//	$m_id = isset($config['m_id']) ? $config['m_id'] : 0;
	//	$cart_id = isset($config['cart_id']) ? $config['cart_id'] : 0;
	//	$t_id = isset($config['t_id']) ? $config['t_id'] : 0;
	//	$before = isset($config['before'])
	//				? ($before = $config['before']['pq_quantity'])
	//				: ($before = 0);
	//	$after = isset($config['after'])
	//			? ($after = $config['after']['pq_quantity'])
	//			: ($after = 0);
	//
	//	//get stock history action
	//	$this->db->where('sha_code' , $action);
	//	$res = $this->db->get('stock_history_action');
	//	$stock_history_action = $res->row_array();
	//	$sha_id = $stock_history_action['sha_id'];
	//
	//	//get data
	//	$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
	//	$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
	//	$this->db->where('pq_id' , $id);
	//	$res = $this->db->get('product_quantity');
	//	$data = $res->row_array();
	//	$this->sci->assign('data' , $data);
	//
	//	$this->db->set('pq_id' , $id);
	//	$this->db->set('sh_code' , $action);
	//	$this->db->set('sha_id' , $sha_id);
	//	$this->db->set('p_id' , $data['p_id']);
	//	$this->db->set('sh_before' , $before);
	//	$this->db->set('sh_after' , $after);
	//	$this->db->set('sh_change' , $change);
	//	$this->db->set('m_id' , $m_id);
	//	$this->db->set('u_id' , $u_id);
	//	$this->db->set('cart_id' , $cart_id);
	//	$this->db->set('t_id' , $t_id);
	//	$this->db->set('sh_note' , $note);
	//	$this->db->set('sh_is_auto' , $is_auto);
	//	$this->db->set('sh_entry' , 'NOW()', FALSE);
	//	$this->db->insert('stock_history');
	//}


	function get_product_subcategories_by_pc_id($pc_id=0) {
		$this->db->where('pc_id' , $pc_id);
		$this->db->where('psc_status' , 'Active');
		$this->db->order_by('psc_name' , 'asc');
		$res = $this->db->get('product_subcategory');
		$result = $res->result_array();
		return $result;
	}

}

?>
