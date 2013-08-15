<?php

class Cart_giftcard extends MY_Controller {

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

		$option = '';
		
		//get product
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product');
		$product = $res->row_array();

		$ok = $this->icart->insert_giftcard($p_id , $quantity);
		
		if( $ok ) {
			$return['status'] = 'ok';
		} else {
			$return['status'] = 'error';
			$return['msg'] = $_POST;
		}
		echo json_encode($return);
	}
	
	
	function view_cart() {
		$this->db->where('m_id' , $this->userinfo['m_id'] );
		$res = $this->db->get('cart_giftcard');
		$cart = $res->result_array();
		$this->sci->assign('cart' , $cart);
		$this->sci->da('view_cart.htm');
	}
	
	function mini_view_cart() {
		$cart = $this->icart->get_cart_giftcard();
		$this->sci->assign('cart' , $cart);
		$cart_subtotal = $cart['cart_subtotal'];
		$this->sci->assign('cart_subtotal' , $cart_subtotal);  
		$this->sci->d('mini_view_cart.htm');
	}
	
	function remove_cart_item($id = '') {
		$ok = $this->icart->remove_giftcard($id);
		redirect( site_url()."product/list_gift_card");
	}
	
	
	
	
	
	//////////////////////////////////////////////
	
	
	
	
	

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
