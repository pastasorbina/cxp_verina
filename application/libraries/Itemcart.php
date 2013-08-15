<?php

class Itemcart  {

	var $cart;
	var $use_cookies = FALSE;

	function Itemcart() {
		$this->CI =& get_instance();
		$this->cart = array();

		$this->load();
	}

	function add_to_cart($i_id , $quantity) {

		// Get item information
		$this->CI->db->where('i_id' , $i_id);
		$res = $this->CI->db->get('item');
		if ($res->num_rows <= 0) return;
		$_item = $res->row();

		$item = new stdClass();
		$item->uniq = uniqid();
		$item->id = $i_id;
		$item->quantity = $quantity;
		array_push($this->cart , $item);
	}

	function get_cart() {
		return ($this->cart);
	}

	function delete_item($uniq) {
		foreach ($this->cart as $key=>$val) {
			if ($val->uniq == $uniq) {
				unset($this->cart[$key]);
				return;
			}
		}
	}

	function flush() {
		$this->cart = array();
	}

	function save() {
		$this->CI->session->set_userdata('cart_data' , base64_encode(serialize($this->cart)));
	}

	function load() {
		if ($this->CI->session->userdata('cart_data')) {
			$this->cart = unserialize(base64_decode($this->CI->session->userdata('cart_data')));
		}
	}


}
?>
