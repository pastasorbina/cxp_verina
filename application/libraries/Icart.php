<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Icart {

	var $use_cookies = FALSE;
	var $userinfo;
	private $CI;

	function __construct() {
		$this->CI =& get_instance();
		$this->userinfo = $this->CI->session->get_userinfo('member');

		$this->CI->db->where('c_key' , 'cart_timeout');
		$res = $this->CI->db->get('config');
		$cart_timeout = $res->row_array();
		$this->cart_timeout = $cart_timeout['c_value'];
		//print $this->cart_timeout;

		$this->CI->load->model('mod_product');
		$this->CI->load->model('mod_stock');

		//get cart last stamp
		if($this->userinfo) {
			$this->CI->db->where('m_id' , $this->userinfo['m_id']);
			$this->CI->db->where('cart_status' , 'Active');
			$this->CI->db->order_by('cart_entry' , 'DESC');
			$this->CI->db->select('*, YEAR(cart_entry) as y, MONTH(cart_entry) as m, DAY(cart_entry) as d, SECOND(cart_entry) as s, MINUTE(cart_entry) as i, HOUR(cart_entry) as h ');
			$res = $this->CI->db->get('cart');
			$last_cart = $res->row_array();
			//print_r($last_cart);

			if($last_cart) {
				$lastcart_stamp = $last_cart['cart_stamp'];
				$this->CI->sci->assign('lastcart_stamp' , $lastcart_stamp);
				$this->CI->sci->assign('lastcart_y' , $last_cart['y']);
				$this->CI->sci->assign('lastcart_m' , $last_cart['m']);
				$this->CI->sci->assign('lastcart_d' , $last_cart['d']);
				$this->CI->sci->assign('lastcart_h' , $last_cart['h']);
				$this->CI->sci->assign('lastcart_i' , $last_cart['i']);
				$this->CI->sci->assign('lastcart_s' , $last_cart['s']);

				//print $lastcart_stamp;
				$lastcart_stamp_unix = human_to_unix($lastcart_stamp);
				$lastcart_expiry = $lastcart_stamp_unix + $this->cart_timeout;
				$lastcart_expiry = strftime('%Y-%m-%d %H:%M:%S', $lastcart_expiry);
				//print /*$lastcart_stamp_stamp*/ ;
				//print date('Y-m-d H:i:s');
				$now = date('Y-m-d H:i:s');

				if($lastcart_expiry < $now) {
					$this->reset_by_m_id($this->userinfo['m_id']);
				} else {
					$lastcart_diff = datediff($now, $lastcart_expiry);
					//print "now "; print date('Y-m-d H:i:s'); print "<br>";
					//print "lastcart "; print($last_cart['cart_entry']); print "<br>";
					//print "expired "; print_r($lastcart_expiry); print "<br>";
					//print "diff "; print_r($lastcart_diff);

					$lastcart_i = $lastcart_diff['minute'];
					$this->CI->sci->assign('lastcart_i' , $lastcart_i);
					$lastcart_s = $lastcart_diff['second'];
					$this->CI->sci->assign('lastcart_s' , $lastcart_s);
				}



			}
		}
	}

	function debug() {
		//print_r($this->userinfo);
	}

	function get_cart() {
		$cart_item = array();
		if($this->use_cookies == TRUE){
			$cart_item = $this->cart->contents();
		} else {
			$this->CI->db->join('product_quantity pq' , 'pq.pq_id = cart.pq_id' , 'left');
			$this->CI->db->join('product p' , 'p.p_id = cart.p_id' , 'left');
			$this->CI->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
			$this->CI->db->where('m_id' , $this->userinfo ['m_id']);
			$this->CI->db->where('cart_status' , 'Active');
			$res = $this->CI->db->get('cart');
			$cart_item = $res->result_array();
		}
		$pr_id = 0;
		$br_id = 0;
		$free_shipping = 'No';
		$brand = array();
		if($cart_item) {
			$br_id = $cart_item[0]['br_id'];
			$pr_id = $cart_item[0]['pr_id'];
			$this->CI->db->where('pr_id' , $pr_id);
			$res = $this->CI->db->get('promo');
			$promo = $res->row_array();
			if($brand) {
				$free_shipping = $brand['brp_free_shipping'];
			}
		}
		$cart_subtotal = 0;
		$total_quantity = 0;
		$total_weight = 0;
		foreach ($cart_item as $k=>$tmp) {
			//calculate payout and quantity
			$cart_subtotal += $tmp['cart_subtotal'];
			$total_quantity += $tmp['cart_quantity'];
			$total_weight += ($tmp['p_weight'] * $tmp['cart_quantity']);
		}

		$cart['items'] = $cart_item;
		$cart['cart_subtotal'] = $cart_subtotal;
		$cart['total_weight'] = $total_weight;
		$cart['br_id'] =  $br_id;
		$cart['pr_id'] =  $pr_id;
		$cart['free_shipping'] =  $free_shipping;
		$cart['brand'] = $brand;
		return $cart;
	}


	function update_quantity($cart_id=0, $new_cart_qty=0) {
		$this->CI->db->where('cart_id' , $cart_id);
		$res = $this->CI->db->get('cart');
		$cart = $res->row_array();
		if(!$cart) { return 'no cart'; }
		$curr_cart_qty = $cart['cart_quantity'];
		$pq_id = $cart['pq_id'];

		//kalau qty baru lebih besar dari cart
		if($new_cart_qty > $curr_cart_qty) {
			$diff = $new_cart_qty - $curr_cart_qty;
			$action = 'cart_in';
		} else {
			$diff = $curr_cart_qty - $new_cart_qty;
			$action = 'cart_out';
		}

		//get current product quantity
		$this->CI->db->where('pq_id' , $pq_id);
		$this->CI->db->where('pq_status' , 'Active');
		$res = $this->CI->db->get('product_quantity');
		$pq = $res->row_array();

		//kalau perubahan lebih besar dari quantity sekarang, batalkan, hanya kalau actionnya menambah item di cart
		if($action == 'cart_in') {
			if($diff > $pq['pq_quantity']) { return 'out of stock'; }
		}
		

		$this->CI->db->trans_start();
			//update cart
			$subtotal = $cart['cart_price'] * $new_cart_qty;
			$this->CI->db->where('cart_id' , $cart_id);
			$this->CI->db->set('cart_quantity' , $new_cart_qty);
			$this->CI->db->set('cart_subtotal' ,  $subtotal);
			$this->CI->db->set('cart_stamp' , date('Y-m-d H:i:s'));
			$this->CI->db->update('cart');
			$affected_rows = $this->CI->db->affected_rows();

			if($affected_rows > 0) {
				//perubahan stock
				$config = array();
				$config['id'] = $pq_id;
				$config['change'] = $diff;
				$config['m_id'] = $this->userinfo['m_id'];
				$config['cart_id'] = $cart_id;
				$config['action'] = $action;
				$config['note'] = 'update cart';
				if($action == 'cart_in') {
					$this->CI->mod_stock->stock_out($config);
				} else {
					$this->CI->mod_stock->stock_in($config);
				}
			}
		$this->CI->db->trans_complete();
		return 'ok';
	}
	
	
	
	

	function insert( $p_id=0, $pr_id=0, $pq_id=0, $quantity=0, $type) {
		$m_id = $this->userinfo['m_id'];

		//get product
		$this->CI->db->where('p_id' , $p_id);
		$res = $this->CI->db->get('product');
		$product = $res->row_array();
		$price = $product['p_price'];

		if($type == 'Giftcard') {
			//kalau tipe item adalah giftcard
			$this->CI->db->trans_start();
			if($this->use_cookies == TRUE){
				//if using cookies
			} else { //if using database
				//check if item already exist in cart 
				$this->CI->db->where('p_id' , $p_id);
				$this->CI->db->where('m_id' , $m_id);
				$this->CI->db->where('cart_status' , 'Active');
				$res = $this->CI->db->get('cart');
				$existing = $res->row_array();

				if($existing) {
					$cart_id = $existing['cart_id'];
					$new_quantity = $existing['cart_quantity'] + $quantity;
					$subtotal = $price * $new_quantity;
					$this->CI->db->where('cart_id' , $cart_id);
					$this->CI->db->set('cart_quantity' , $new_quantity);
					$this->CI->db->set('cart_subtotal' ,  $subtotal);
					$this->CI->db->set('cart_entry' , 'NOW()', FALSE );
					$this->CI->db->update('cart');
					$udpateok = $this->CI->db->affected_rows();
				} else {
					$subtotal = $price * $quantity;
					$this->CI->db->set('p_id' , $p_id);
					$this->CI->db->set('m_id' , $m_id);
					$this->CI->db->set('cart_entry' , 'NOW()', FALSE );
					$this->CI->db->set('cart_quantity' , $quantity);
					$this->CI->db->set('cart_price' ,  $price);
					$this->CI->db->set('cart_subtotal' ,  $subtotal);
					$this->CI->db->set('cart_name' , $product['p_name'] );
					$this->CI->db->insert('cart');
					$udpateok = $this->CI->db->affected_rows();
					$cart_id = $this->CI->db->insert_id();
				}
			}
		}else {
			//kalau tipe item adalah product
			//get product quantity
			$this->CI->db->where('pq_id' , $pq_id);
			$this->CI->db->where('pq_status' , 'Active');
			$res = $this->CI->db->get('product_quantity');
			$pq = $res->row_array();

			//make sure quantity cannot below stock
			if($quantity > $pq['pq_quantity']) { return FALSE; }

			$this->CI->db->trans_start();
			if($this->use_cookies == TRUE){
				//if using cookies
			} else { //if using database
				//check if brand conflict 

				//check if item already exist in cart
				$this->CI->db->where('pr_id' , $pr_id);
				$this->CI->db->where('p_id' , $p_id);
				$this->CI->db->where('pq_id' , $pq_id);
				$this->CI->db->where('m_id' , $m_id);
				$this->CI->db->where('cart_status' , 'Active');
				$res = $this->CI->db->get('cart');
				$existing = $res->row_array();

				$price = $product['p_discount_price'];
				$br_id = $product['br_id'];
				if($existing) {
					$cart_id = $existing['cart_id'];
					$new_quantity = $existing['cart_quantity'] + $quantity;
					$subtotal = $price * $new_quantity;
					$this->CI->db->where('cart_id' , $cart_id);
					$this->CI->db->set('cart_quantity' , $new_quantity);
					$this->CI->db->set('cart_subtotal' ,  $subtotal);
					$this->CI->db->set('cart_entry' , 'NOW()', FALSE );
					$this->CI->db->update('cart');
					$udpateok = $this->CI->db->affected_rows();
				} else {
					$subtotal = $price * $quantity;
					$this->CI->db->set('pr_id' , $pr_id);
					$this->CI->db->set('p_id' , $p_id);
					$this->CI->db->set('pq_id' , $pq_id);
					$this->CI->db->set('m_id' , $m_id);
					$this->CI->db->set('cart_entry' , 'NOW()', FALSE );
					$this->CI->db->set('cart_quantity' , $quantity); 
					$this->CI->db->set('cart_price' ,  $price);
					$this->CI->db->set('cart_subtotal' ,  $subtotal);
					$this->CI->db->set('cart_name' , $product['p_name'] );
					$this->CI->db->insert('cart');
					$udpateok = $this->CI->db->affected_rows();
					$cart_id = $this->CI->db->insert_id();
				}
			}

			if($udpateok > 0) {
				//perubahan stock
				$config = array();
				$config['id'] = $pq_id;
				$config['change'] = $quantity;
				$config['action'] = 'cart_in';
				$config['m_id'] = $m_id;
				$config['cart_id'] = $cart_id;
				$config['note'] = 'insert into cart';
				$this->CI->mod_stock->stock_out($config);
			}
		}

		$ok = $this->CI->db->trans_complete();
		return $ok;
	}


	function check_if_brand_is_different($p_id){
		$m_id = $this->userinfo['m_id'];

		//get product
		$this->CI->db->where('p_id' , $p_id);
		$res = $this->CI->db->get('product');
		$product = $res->row_array();
		$br_id = $product['br_id'];

		//get cart
		$this->CI->db->join('product p' , 'p.p_id = cart.p_id' , 'left');
		$this->CI->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->CI->db->where('m_id' , $m_id);
		$this->CI->db->where('cart_status' , 'Active');
		$res = $this->CI->db->get('cart');
		$cart = $res->row_array();

		if(!$cart) { return FALSE; }
		//compare
		if($cart['br_id'] != $br_id) {
			return TRUE;
		} else {
			return FALSE;
		}

	}

	function check_if_cart_overlimit($p_id, $inserted_quantity){
		$m_id = $this->userinfo['m_id'];
		$site_config = $this->CI->config->item('site_config');
		$max_quantity = $site_config['cart_max_product_quantity'];
		$max_quantity = intval($max_quantity);
		$total = 0;
		$ok = TRUE;

		//get product in cart, regardless of quantity
		$this->CI->db->where('p_id' , $p_id);
		$this->CI->db->where('m_id' , $m_id);
		$this->CI->db->where('cart_status' , 'Active');
		$res = $this->CI->db->get('cart');
		$cart = $res->result_array();
		foreach($cart as $k=>$tmp) {
			$total += $tmp['cart_quantity'];
		}
		$total = $total + $inserted_quantity;
		if($total <= $max_quantity) { $status = FALSE ; }else{ $status =  TRUE; }
		return $status;
	}



	function reset_by_m_id( $m_id=0, $config=array() ){
		$this->CI->db->trans_start();
			//get all user cart
			$this->CI->db->where('m_id' , $m_id);
			$this->CI->db->where('cart_status' , 'Active');
			$res = $this->CI->db->get('cart');
			$usercart = $res->result_array();

			//iterate per cart item
			foreach($usercart as $k=>$tmp) {
				$cart_id = $tmp['cart_id'];
				$this->CI->db->where('cart_id' , $cart_id );
				$res = $this->CI->db->get('cart');
				$cart = $res->row_array();
				$cart_quantity = $cart['cart_quantity'];
				$pq_id = $cart['pq_id'];

				//delete cart
				$this->CI->db->where('cart_id' , $cart_id);
				$this->CI->db->set('cart_status' , 'Deleted');
				$ok = $this->CI->db->update('cart');

				//perubahan stock
				$config['id'] = $pq_id;
				$config['change'] = $cart_quantity;
				$config['m_id'] = $this->userinfo['m_id'];
				$config['cart_id'] = $cart_id;
				$config['action'] = 'cart_out';
				$config['note'] = 'reset cart';
				$this->CI->mod_stock->stock_in($config);
			}

		$this->CI->db->trans_complete();
		return $this->CI->db->trans_status();
	}

	function remove( $cart_id=0, $config=array() ) {
		$this->CI->db->trans_start();
			$this->CI->db->where('cart_id' , $cart_id);
			$res = $this->CI->db->get('cart');
			$cart = $res->row_array();
			$cart_quantity = $cart['cart_quantity'];
			$pq_id = $cart['pq_id'];

			//if using database
			$this->CI->db->where('cart_id' , $cart_id);
			$this->CI->db->set('cart_status' , 'Deleted');
			$this->CI->db->update('cart');
			$affected_rows = $this->CI->db->affected_rows();
			if($affected_rows > 0) {

				//perubahan stock
				$config['id'] = $pq_id;
				$config['change'] = $cart_quantity;
				$config['m_id'] = $this->userinfo['m_id'];
				$config['cart_id'] = $cart_id;
				$config['action'] = 'cart_out';
				$config['note'] = 'remove from cart';
				$this->CI->mod_stock->stock_in($config);
			}
		$this->CI->db->trans_complete();
		return $this->CI->db->trans_status();

	}

	function clear(){
		$m_id = $this->userinfo['m_id'];
		$this->CI->db->where('m_id' , $m_id);
		$this->CI->db->set('cart_status' , 'Deleted');
		$ok = $this->CI->db->update('cart');
		return $ok;
	}

	function get_cart_count() {
		$m_id = $this->userinfo['m_id'];
		$this->CI->db->where('m_id' , $m_id);
		$count = $this->CI->db->count_all_results('cart');
	}

	
	
	
	
	
	
	
	
	
	
	function get_cart_giftcard() {
		$cart_item = array();
		if($this->use_cookies == TRUE){
			$cart_item = $this->cart->contents();
		} else { 
			$this->CI->db->join('product p' , 'p.p_id = cart_giftcard.p_id' , 'left'); 
			$this->CI->db->where('m_id' , $this->userinfo ['m_id']);
			$this->CI->db->where('cg_status' , 'Active');
			$res = $this->CI->db->get('cart_giftcard');
			$cart_item = $res->result_array();
		}
		$pr_id = 0;
		$br_id = 0;
		$free_shipping = 'No';
		$brand = array(); 
		$cart_subtotal = 0;
		$total_quantity = 0;
		$total_weight = 0;
		foreach ($cart_item as $k=>$tmp) {
			//calculate payout and quantity
			$cart_subtotal += $tmp['cg_subtotal'];
			$total_quantity += $tmp['cg_quantity'];
			$total_weight += ($tmp['p_weight'] * $tmp['cg_quantity']);
		}

		$cart['items'] = $cart_item;
		$cart['cart_subtotal'] = $cart_subtotal;
		$cart['total_weight'] = $total_weight;
		$cart['br_id'] =  $br_id;
		$cart['pr_id'] =  $pr_id;
		$cart['free_shipping'] =  $free_shipping;
		$cart['brand'] = $brand;
		return $cart;
	}
	
	function insert_giftcard( $p_id=0, $quantity=0) {
		$m_id = $this->userinfo['m_id'];

		//get product
		$this->CI->db->where('p_id' , $p_id);
		$res = $this->CI->db->get('product');
		$product = $res->row_array();
		$price = $product['p_price'];
		
		$this->CI->db->trans_start();
		//check if item already exist in cart 
		$this->CI->db->where('p_id' , $p_id);
		$this->CI->db->where('m_id' , $m_id);
		$this->CI->db->where('cg_status' , 'Active');
		$res = $this->CI->db->get('cart_giftcard');
		$existing = $res->row_array();

		if($existing) {
			$cg_id = $existing['cg_id'];
			$new_quantity = $existing['cg_quantity'] + $quantity;
			$subtotal = $price * $new_quantity;
			$this->CI->db->where('cg_id' , $cg_id);
			$this->CI->db->set('cg_quantity' , $new_quantity);
			$this->CI->db->set('cg_subtotal' ,  $subtotal);
			$this->CI->db->set('cg_entry' , 'NOW()', FALSE );
			$this->CI->db->update('cart_giftcard');
			$udpateok = $this->CI->db->affected_rows();
		} else {
			$subtotal = $price * $quantity;
			$this->CI->db->set('p_id' , $p_id);
			$this->CI->db->set('m_id' , $m_id);
			$this->CI->db->set('cg_entry' , 'NOW()', FALSE );
			$this->CI->db->set('cg_quantity' , $quantity);
			$this->CI->db->set('cg_price' ,  $price);
			$this->CI->db->set('cg_subtotal' ,  $subtotal);
			$this->CI->db->set('cg_name' , $product['p_name'] );
			$this->CI->db->insert('cart_giftcard');
			$udpateok = $this->CI->db->affected_rows();
			$cg_id = $this->CI->db->insert_id();
		}
		$ok = $this->CI->db->trans_complete();
		return $ok;
	}
	
	function remove_giftcard( $cg_id=0, $config=array() ) {
		$this->CI->db->trans_start();
			$this->CI->db->where('cg_id' , $cg_id);
			$res = $this->CI->db->get('cart_giftcard');
			$cart = $res->row_array();
			$cg_quantity = $cart['cg_quantity']; 

			//if using database
			$this->CI->db->where('cg_id' , $cg_id);
			$this->CI->db->set('cg_status' , 'Deleted');
			$this->CI->db->update('cart_giftcard');
			$affected_rows = $this->CI->db->affected_rows();
			if($affected_rows > 0) { 
			}
		$this->CI->db->trans_complete();
		return $this->CI->db->trans_status(); 
	}
	
	
	function update_quantity_giftcard($cg_id=0, $new_cg_qty=0) {
		$this->CI->db->where('cg_id' , $cg_id);
		$res = $this->CI->db->get('cart_giftcard');
		$cart = $res->row_array();
		if(!$cart) { return 'no cart'; }
		$curr_cg_qty = $cart['cg_quantity']; 
    
		$this->CI->db->trans_start();
			//update cart
			$subtotal = $cart['cg_price'] * $new_cg_qty;
			$this->CI->db->where('cg_id' , $cg_id);
			$this->CI->db->set('cg_quantity' , $new_cg_qty);
			$this->CI->db->set('cg_subtotal' ,  $subtotal);
			$this->CI->db->set('cg_stamp' , date('Y-m-d H:i:s'));
			$this->CI->db->update('cart_giftcard');
			$affected_rows = $this->CI->db->affected_rows();
 
		$this->CI->db->trans_complete();
		return 'ok';
	}
	
	function clear_giftcard(){
		$m_id = $this->userinfo['m_id'];
		$this->CI->db->where('m_id' , $m_id);
		$this->CI->db->set('cg_status' , 'Deleted');
		$ok = $this->CI->db->update('cart_giftcard');
		return $ok;
	}
}
