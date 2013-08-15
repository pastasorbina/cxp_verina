<?php

class Cart extends MY_Controller {

	var $mod_title = '';
	var $use_cookies = FALSE;
	var $userinfo;

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->userinfo = $this->session->get_userinfo('member');
		$this->load->library('icart');
	}

	//function get_cart_expiry() {
	//	if($this->userinfo) {
	//		$this->db->where('m_id' , $this->userinfo['m_id']);
	//		$this->db->where('cart_status' , 'Active');
	//		$this->db->order_by('cart_stamp' , 'DESC');
	//		$this->db->select('*, YEAR(cart_stamp) as y, MONTH(cart_stamp) as m, DAY(cart_stamp) as d, SECOND(cart_stamp) as s, MINUTE(cart_stamp) as i, HOUR(cart_stamp) as h ');
	//		$res = $this->db->get('cart');
	//		$last_cart = $res->row_array();
	//		//print_r($last_cart);
	//
	//		if($last_cart) {
	//			$lastcart_stamp = $last_cart['cart_stamp'];
	//			$this->sci->assign('lastcart_stamp' , $lastcart_stamp);
	//			$this->sci->assign('lastcart_y' , $last_cart['y']);
	//			$this->sci->assign('lastcart_m' , $last_cart['m']);
	//			$this->sci->assign('lastcart_d' , $last_cart['d']);
	//			$this->sci->assign('lastcart_h' , $last_cart['h']);
	//			$this->sci->assign('lastcart_i' , $last_cart['i']);
	//			$this->sci->assign('lastcart_s' , $last_cart['s']);
	//
	//			//$expiry = datediff($lastcart_stamp)
	//
	//		}
	//	}
	//}

	function debug() {
		$userinfo = $this->session->get_userinfo();
		print_r($this->userinfo);
	}

	function insert() {
		$p_id = $this->input->post('p_id');
		$quantity = $this->input->post('quantity');
		$pq_id = $this->input->post('size');
		$p_type = $this->input->post('p_type');
		$pr_id = $this->input->post('pr_id'); 

		$option = '';
		
		//get product
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product');
		$product = $res->row_array();

		if($p_type == 'Giftcard') {
			$ok = $this->icart->insert($p_id, 0, 0, $quantity, $p_type);
		} else {
			//check if brand conflict
			if($this->icart->check_if_brand_is_different($p_id)) {
				$return['status'] = 'error';
				$return['msg'] = 'You cannot add product from different brands at one time, please complete your current transaction first';
				echo json_encode($return);
				return FALSE;
			}
			//check if cart is overlimit for this product
			if($this->icart->check_if_cart_overlimit($p_id, $quantity)) {
				$return['status'] = 'error';
				$return['msg'] = 'cannot add item to the basket, you have exceeded allowed number of items for this product';
				echo json_encode($return);
				return FALSE;
			}
			//check if selected quantity is over the stock
			$this->db->where('p_id' , $p_id);
			$this->db->where('pq_id' , $pq_id);
			$res = $this->db->get('product_quantity');
			$product_quantity = $res->row_array();
			if($quantity > $product_quantity['pq_quantity'] ) {
				$return['status'] = 'error';
				$return['msg'] = 'cannot add item to the basket, quantity you selected exceeded number of stock';
				echo json_encode($return);
				return FALSE;
			}

			//if ok to submit
			$ok = $this->icart->insert($p_id, $pr_id, $pq_id, $quantity, $p_type);
		}

		if( $ok ) {
			$return['status'] = 'ok';
		} else {
			$return['status'] = 'error';
			$return['msg'] = $_POST;
		}
		echo json_encode($return);
	}

	function get_cart() {
		$cart = $this->icart->get_cart();
		return $cart;
	}

	function display_cart() {
		$cart = $this->icart->get_cart();
		$this->sci->assign('cart' , $cart);
		$this->sci->d('cart_snippet.htm');
	}

	function ajax_get_total_qty() {
		$total_qty=0;
		if($this->use_cookies == TRUE){
			$total_qty = $this->cart->total_items();
		} else {
			$cart = $this->icart->get_cart();
			foreach($cart['items'] as $tmp) { $total_qty += $tmp['cart_quantity']; }
		}
		$data['total_qty'] = $total_qty;
		print json_encode($data);
	}

	function destroy(){
		$this->cart->destroy();
	}

	function ajax_remove($id = '') {
		$ok = $this->icart->remove($id);
		//$ok = true;
		if( $ok ) {
			$cart_count = $this->mod_cart->get_cart_count();
			if($cart_count == 0){
				$return['status'] = 'ok';
				$return['substatus'] = 'cart empty';
			} else {
				$return['status'] = 'ok';
				$return['substatus'] = 'cart not empty';
			}


		} else {
			$return['status'] = 'error';
			$return['msg'] = 'error removing item';
		}
		echo json_encode($return);
	}

	function ajax_success() {
		$this->sci->d('cart_ajax_success.htm');
	}

	function ajax_reset() {
		$ok = $this->icart->reset_by_m_id($this->userinfo['m_id']);

		$ret = array();
		if($ok) {
			$ret['status'] = 'ok';
		} else {
			$ret['status'] = 'error';
		}
		echo json_encode($ret);
	}


}
