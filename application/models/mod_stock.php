<?php

class Mod_stock extends MY_Model {

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
	}

	function stock_in($config=array() ) {
		if(empty($config)){ return FALSE; }
		$ok = FALSE;
		$action = isset($config['action']) ? $config['action'] : 'stock_out';
		$id = isset($config['id']) ? $config['id'] : 0;
		$change = isset($config['change']) ? $config['change'] : 0;

		$lock = db_get_lock('stock_change');
		//$sql = "
		//	LOCK TABLES product_quantity WRITE
		//";
		//$res = $this->db->query($sql , array());
		if($lock == 1) {
			//start
			$this->db->trans_start();

			//getstock before
			$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
			$res = $this->db->query($query_string);
			$config['before'] = $res->row_array();

			//update product quantity
			$query_string = "UPDATE product_quantity SET pq_quantity = pq_quantity+$change WHERE pq_id = $id ;";
			$this->db->query($query_string);
			$affected_rows = $this->db->affected_rows();

			if($affected_rows > 0) {
				//get stock after
				$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
				$res = $this->db->query($query_string);
				$config['after'] = $res->row_array();
				//do stock history
				$this->stock_history($config);
			}
			//complete
			$this->db->trans_complete();
			$ok = $this->db->trans_status();
		}
		//$sql = "
		//	UNLOCK TABLES
		//";
		//$res = $this->db->query($sql , array());
		db_release_lock('stock_change');
		return $ok;
	}

	function stock_out($config=array() ) {
		if(empty($config)){ return FALSE; }
		$ok = FALSE;
		$action = isset($config['action']) ? $config['action'] : 'stock_out';
		$id = isset($config['id']) ? $config['id'] : 0;
		$change = isset($config['change']) ? $config['change'] : 0;

		$lock = db_get_lock('stock_change');
		// LOCK TABLES product_quantity , a ,b  ,c , d
		if($lock == 1) {
			//start
			$this->db->trans_start();

			//getstock before
			$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
			$res = $this->db->query($query_string);
			$config['before'] = $res->row_array();

			//update product quantity
			$query_string = "
				UPDATE product_quantity SET pq_quantity = pq_quantity-$change
				WHERE pq_id = $id AND pq_quantity >= $change
				;";
			$this->db->query($query_string);
			$affected_rows = $this->db->affected_rows();

			if($affected_rows > 0) {
				//get stock after
				$query_string = "SELECT pq_quantity FROM product_quantity WHERE pq_id = $id;";
				$res = $this->db->query($query_string);
				$config['after'] = $res->row_array();
				//do stock history
				$this->stock_history($config);
			}
			//complete
			$this->db->trans_complete();
			$ok = $this->db->trans_status();
		}
		db_release_lock('stock_change');
		return $ok;
	}

	function stock_history($config=array() ) {
		//breakdown config
		$action = isset($config['action']) ? $config['action'] : '';
		$id = isset($config['id']) ? $config['id'] : 0;
		$change = isset($config['change']) ? $config['change'] : 0;
		$note = isset($config['note']) ? $config['note'] : '';
		$is_auto = isset($config['is_auto']) ? $config['is_auto'] : 'No';
		$u_id = isset($config['u_id']) ? $config['u_id'] : 0;
		$m_id = isset($config['m_id']) ? $config['m_id'] : 0;
		$cart_id = isset($config['cart_id']) ? $config['cart_id'] : 0;
		$trans_id = isset($config['trans_id']) ? $config['trans_id'] : 0;
		$before = isset($config['before'])
					? ($before = $config['before']['pq_quantity'])
					: ($before = 0);
		$after = isset($config['after'])
				? ($after = $config['after']['pq_quantity'])
				: ($after = 0);

		//get stock history action
		$this->db->where('sha_code' , $action);
		$res = $this->db->get('stock_history_action');
		$stock_history_action = $res->row_array();
		$sha_id = $stock_history_action['sha_id'];

		//get data
		$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('pq_id' , $id);
		$res = $this->db->get('product_quantity');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		//$this->db->set('pq_id' , $id);
		//$this->db->set('sh_code' , $action);
		//$this->db->set('sha_id' , $sha_id);
		//$this->db->set('p_id' , $data['p_id']);
		//$this->db->set('sh_before' , $before);
		//$this->db->set('sh_after' , $after);
		//$this->db->set('sh_change' , $change);
		//$this->db->set('m_id' , $m_id);
		//$this->db->set('u_id' , $u_id);
		//$this->db->set('cart_id' , $cart_id);
		//$this->db->set('trans_id' , $trans_id);
		//$this->db->set('sh_note' , $note);
		//$this->db->set('sh_is_auto' , $is_auto);
		//$this->db->set('sh_entry' , 'NOW()', FALSE);
		//$this->db->insert('stock_history');
	}

}

?>
