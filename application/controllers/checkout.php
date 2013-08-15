<?php

class Checkout extends MY_Controller {

	var $mod_title = '';
	var $use_cookies = FALSE;

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->session->validate_member();

		$this->load->model('mod_product');
		$this->load->model('mod_product_quantity');
		$this->load->model('mod_member_address');
		$this->load->model('mod_shipping_method');
		$this->load->model('mod_payment_method');

		$this->load->model('mod_checkout');

		$this->sci->assign('mod_title' , $this->mod_title);
		$this->load->library('cart');

		$this->userinfo = $this->session->get_userinfo('member');

		//get last brand visited
		$last_brand_visited = $this->session->get_bread('brand-view');
		$this->sci->assign('last_brand_visited' , $last_brand_visited);

		$this->icart->debug();
	}

	function _load_topbar(){ $html = $this->sci->fetch('checkout/topbar.htm'); 	$this->sci->assign('checkout_topbar' , $html);	}
	function index() { redirect(site_url().'checkout/view_checkout'); }

	function view_cart() {
		$this->session->set_bread('list');

		$cartdata = $this->_get_cart_data();
		$this->sci->assign('cart' , $cartdata['cart_data']);
		$this->sci->assign('cart_final_payout' , $cartdata['cart_final_payout']);
		$saldo = $this->_get_saldo();
		$this->sci->assign('saldo' , $saldo);

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Shopping Cart";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->_load_topbar();
		$this->sci->da('view_cart.htm');
	}

	function reset_cart(){
		$ok = $this->icart->reset_by_m_id($this->userinfo['m_id']);
		redirect( $this->mod_url."view_cart");
	}

	function remove_cart_item($id = '') {
		$ok = $this->icart->remove($id);
		redirect( $this->mod_url."view_checkout");
	}

	function update_cart_qty() {
		$cart_id = $this->input->post('id');
		$new_qty = $this->input->post('qty');

		if($new_qty > 5) {
			$ret['status'] = 'error';
			$ret['msg'] = 'quantity cannot exceed 5';
			echo json_encode($ret);
			return false;
		}
		$ok  = $this->icart->update_quantity($cart_id, $new_qty);
		switch($ok) {
			case 'ok' :
				$ret['status'] = 'ok';
				$ret['msg'] = 'ok';
				break;
			case 'no cart' :
				$ret['status'] = 'error';
				$ret['msg'] = 'error updating cart, try refreshing the page';
				break;
			case 'out of stock' :
				$ret['status'] = 'error';
				$ret['msg'] = 'Quantity exceed the number of items in stock';
				break;
		}
		echo json_encode($ret);
	}

	function set_shipping() {
		$this->sci->in_development();
	}

	function _get_saldo() {
		$saldo = $this->mod_member->get_saldo($this->userinfo['m_id']);
		return $saldo;
	}
	
	function _count_grandtotal($count_cartsubtotal=0, $count_voucher=0, $count_shipping=0) {
		$grandtotal = 0;
		$after_voucher = 0;
		$after_voucher = ( $count_cartsubtotal - $count_voucher );
		if($after_voucher < 0 ) { $after_voucher = 0; }
		$grandtotal = $after_voucher + $count_shipping;
		$grandtotal = ceil($grandtotal);
		return $grandtotal;
	}
	
	function _count_totalpayout($grandtotal=0, $usecredit=0) {
		$payout = 0;
		$payout = $grandtotal - $usecredit; 
		$payout = ceil($payout);
		return $payout;
	}
	
	function _count_add_unique($payout=0, $unique=0) {
		$payout = $payout + $unique;
		$payout = ceil($payout);
		return $payout;
	}
	
	function ajax_count_grandtotal() {
		$count_cartsubtotal = $this->input->post('count_cartsubtotal');
		$count_voucher = $this->input->post('count_voucher');
		$count_shipping = $this->input->post('count_shipping');
		
		$grandtotal = $this->_count_grandtotal($count_cartsubtotal, $count_voucher, $count_shipping);
		$ret['grandtotal_raw'] = $grandtotal;
		$ret['grandtotal'] = price_format($grandtotal);
		echo json_encode($ret);
	}

	/* get cart data */
	//function _get_cart_data(){
	//	$cart = $this->icart->get_cart();
	//	//$saldo = $this->_get_saldo();
	//	//$cart_subtotal = 0;
	//	//$cart_final_payout = $cart['total_payout'] - $saldo;
	//	$cart_final_payout = $cart['total_payout'];
	//	$brand = $cart['brand'];
	//
	//	$data['cart_data'] = $cart;
	//	$data['cart_brand'] = $brand;
	//	$data['cart_br_id'] = $cart['br_id'];
	//	$data['cart_brp_id'] = $cart['brp_id'];
	//	$data['cart_free_shipping'] = $cart['free_shipping'];
	//	$data['cart_final_payout'] = $cart_final_payout;
	//	$data['cart_total_weight'] = $cart['total_weight'];
	//	return $data;
	//}

	function _calculate_shipping_price($ac_id=0, $sm_id=0){
		//TODO: add shipping calculation by weight here
		$this->db->where('ac_id' , $ac_id);
		$this->db->where('sm_id' , $sm_id);
		$this->db->where('sp_disabled' , 'No');
		$res = $this->db->get('shipping_price');
		$sp = $res->row_array();

		if(!$sp){ return false; } else {
			$price = $sp['sp_price'];
			return $price;
		}
	}

	//function _calculate_shipping_price_by_address($madr_id=0, $sm_id=0){
	//	//get city
	//	$this->db->where('madr_id' , $madr_id);
	//	$res = $this->db->get('member_address');
	//	$address = $res->row_array();
	//	$ac_id = $address['ac_id'];
	//
	//	//TODO: add shipping calculation by weight here
	//	$this->db->where('ac_id' , $ac_id);
	//	$this->db->where('sm_id' , $sm_id);
	//	$this->db->where('sp_disabled' , 'No');
	//	$res = $this->db->get('shipping_price');
	//	$sp = $res->row_array();
	//
	//	if(!$sp){ return false; } else {
	//		$price = $sp['sp_price'];
	//		return $price;
	//	}
	//}

	function get_shipping_price() {
		$madr_id = $this->input->post('shipping_address');
		$sm_id = $this->input->post('shipping_method');
		$total_weight = $this->input->post('total_weight');
		$pr_id = $this->input->post('pr_id');

		//get city
		$shipping_address = $this->mod_checkout->get_member_address($madr_id);
		$ac_id = $shipping_address['ac_id'];
		//get shipping price
		$shipping_price = $this->mod_checkout->calculate_shipping_price_by_address($madr_id, $sm_id, $total_weight, $pr_id);
		$ret['ac_id'] = $ac_id;
		$ret['sm_id'] = $sm_id;

		if($shipping_price != false) {
			$ret['status'] = 'ok';
			$ret['price'] = number_format($shipping_price, 0, '.','');
			$ret['pricelabel'] = number_format($shipping_price, 0, '','.');
			$ret['msg'] = 'shipping price found';
		} else {
			$ret['status'] = 'error';
			$ret['price'] = null;
			$ret['msg'] = 'cannot find shipping price';
		}
		echo json_encode($ret);
	}

	

	function assign_cart() {
		$cart = $this->icart->get_cart();
		$this->sci->assign('cart' , $cart);
		$cart_subtotal = $cart['cart_subtotal']; $this->sci->assign('cart_subtotal' , $cart_subtotal);
		$total_weight =  $cart['total_weight']; $this->sci->assign('total_weight' , $total_weight);
		$brand = $cart['brand']; $this->sci->assign('brand' , $brand);
		$free_shipping = $cart['free_shipping']; 	$this->sci->assign('free_shipping' , $free_shipping);
		$saldo = $this->_get_saldo(); $this->sci->assign('saldo' , $saldo);
		$pr_id = $cart['pr_id']; $this->sci->assign('pr_id' , $pr_id);
	}

	function view_checkout() {
		$this->session->set_bread('checkout');
		$this->load->library('form_validation');
		
		//set breadcrumbs
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Checkout";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		
		//assign cart data to view
		$this->assign_cart();
		
		//get all address of this member
		$this->db->join('area_province ap' , 'ap.ap_id = member_address.ap_id' , 'left');
		$this->db->join('area_city ac' , 'ac.ac_id = member_address.ac_id' , 'left');
		$this->db->where('madr_status' , 'Active');
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$this->db->order_by('madr_entry' , 'DESC');
		$res = $this->db->get('member_address');
		$address = $res->result_array();
		$this->sci->assign('address' , $address);
		
		//get all shipping method
		$this->db->where('sm_status' , 'Active');
		$this->db->order_by('sm_entry' , 'DESC');
		$res = $this->db->get('shipping_method');
		$shipping_method = $res->result_array();
		$this->sci->assign('shipping_method' , $shipping_method);

		//get random unique code
		$unique = $this->mod_checkout->generate_random();
		$this->sci->assign('unique' , $unique);

		$this->form_validation->set_rules('shipping_address','shipping address','trim|required|xss_clean');
		$this->form_validation->set_rules('billing_address','billing address','trim|required|xss_clean');
		$this->form_validation->set_rules('shipping_method','shipping method','trim|required|xss_clean');
		$this->form_validation->set_rules('credit_amount', 'Credit', 'trim|numeric|callback_credit_check');
		$this->form_validation->set_rules('submit_voucher_code', 'Voucher Code', 'trim');
		$this->form_validation->set_rules('send_as_gift', 'Send As Gift', 'trim');
		$this->form_validation->set_rules('pr_id', 'Promo ID', 'trim');
		if($this->form_validation->run() == FALSE) {
			$this->_load_topbar();
			$this->sci->da('view_checkout.htm');
		} else {
			//set input parameters to session
			$_SESSION['pr_id'] = $this->input->post('pr_id');
			$_SESSION['shipping_address_id'] = $this->input->post('shipping_address');
			$_SESSION['billing_address_id'] = $this->input->post('billing_address');
			$_SESSION['shipping_method_id'] = $this->input->post('shipping_method');
			$_SESSION['credit_amount'] = $this->input->post('credit_amount');
			$_SESSION['submit_voucher_code'] = $this->input->post('submit_voucher_code');
			$_SESSION['unique'] = $this->input->post('unique');
			$send_as_gift = $this->input->post('send_as_gift');
			if($send_as_gift) { $send_as_gift = "Yes"; } else { $send_as_gift = "No"; }
			$_SESSION['send_as_gift'] = $send_as_gift;

			redirect( $this->mod_url.'verify_checkout');
		}
	}
	
	public function credit_check($saldo) {
		$user_saldo = $this->userinfo['m_saldo'];
		if ($saldo >  $user_saldo) {
			$this->form_validation->set_message('credit_check', 'Credit used cannot exceed your current credit');
			return FALSE;
		} else {
			//get cart
			$cart = $this->icart->get_cart();  
			$cart_subtotal = $cart['cart_subtotal']; 
			$total_weight =  $cart['total_weight'];  
			$brand = $cart['brand'];  
			$free_shipping = $cart['free_shipping'];   
			$pr_id = $cart['pr_id']; 
			
			//get promo
			$this->db->where('pr_id' , $pr_id);
			$res = $this->db->get('promo');
			$promo = $res->row_array();
			$this->sci->assign('promo' , $promo); 
	
			//get shipping address, billing address and shipping method
			$shipping_address_id = $this->input->post('shipping_address');
			$billing_address_id = $this->input->post('billing_address');
			$shipping_method_id = $this->input->post('shipping_method');
			
			$shipping_address = $this->mod_checkout->get_member_address($shipping_address_id); 
			$billing_address = $this->mod_checkout->get_member_address($billing_address_id); 
			$shipping_method = $this->mod_checkout->get_shipping_method_by_id($shipping_method_id); 
	
			//get voucher
			$voucher_code = $this->input->post('submit_voucher_code'); 
			$voucher_nominal = 0;
			if($voucher_code != '') {
				$return = $this->mod_checkout->get_voucher_by_code($voucher_code, $this->userinfo['m_id'], $cart['cart_subtotal'] );
				if($return['type'] == 'Normal') {
					$voucher_nominal = $return['data']['v_nominal'];
				} elseif($return['type'] == 'Promo') {
					$voucher_nominal = $return['data']['vs_nominal'];
				}
			} 
	
			//calculate shipping_price
			$shipping_price = $this->mod_checkout->calculate_shipping_price_by_address($shipping_address_id, $shipping_method_id, $total_weight,  $cart['pr_id']);  
			//calculate total
			$grandtotal = $this->_count_grandtotal($cart['cart_subtotal'], $voucher_nominal, $shipping_price);
			//$total_payout = $this->_count_totalpayout($grandtotal, $credit_amount); 
			//$total_payout = $this->_count_add_unique($grandtotal, $unique);
			//$grandtotal = $this->
			//$grandtotal = (( $cart['cart_subtotal'] - $voucher_nominal ) + $shipping_price ) ;
			//$total_payout = $grandtotal;
			
			if($saldo > $grandtotal) {
				$this->form_validation->set_message('credit_check', "You cannot use credit larger than your total payout !");
				return FALSE;
			} else {
				return TRUE;
			}
			
		}
	}

	function verify_checkout() {
		$shipping_address_id = @$_SESSION['shipping_address_id'];
		$billing_address_id = @$_SESSION['billing_address_id'];
		$shipping_method_id = @$_SESSION['shipping_method_id'];
		$credit_amount = @$_SESSION['credit_amount'];
		$voucher_code = @$_SESSION['submit_voucher_code'];
		$send_as_gift = @$_SESSION['send_as_gift'];
		$unique = @$_SESSION['unique'];
		$pr_id = @$_SESSION['pr_id']; 
		$this->sci->assign('shipping_address_id' , $shipping_address_id);
		$this->sci->assign('billing_address_id' , $billing_address_id);
		$this->sci->assign('shipping_method_id' , $shipping_method_id);
		$this->sci->assign('credit_amount' , $credit_amount);
		$this->sci->assign('voucher_code' , $voucher_code);
		$this->sci->assign('send_as_gift' , $send_as_gift);
		$this->sci->assign('unique' , $unique);

		//get contents
		$this->db->where('c_code' , 'checkout-disclaimer');
		$res = $this->db->get('content');
		$checkout_disclaimer = $res->row_array();
		$this->sci->assign('checkout_disclaimer' , $checkout_disclaimer);

		//get cart
		$cart = $this->icart->get_cart();
		if(sizeof($cart['items']) == 0) {
			redirect( $this->mod_url.'view_checkout');
		}
		$this->sci->assign('cart' , $cart);
		$cart_subtotal = $cart['cart_subtotal']; $this->sci->assign('cart_subtotal' , $cart_subtotal);
		$total_weight =  $cart['total_weight']; $this->sci->assign('total_weight' , $total_weight);
		$brand = $cart['brand']; $this->sci->assign('brand' , $brand);
		$free_shipping = $cart['free_shipping']; 	$this->sci->assign('free_shipping' , $free_shipping);
		$saldo = $this->_get_saldo(); $this->sci->assign('saldo' , $saldo);
		$pr_id = $cart['pr_id']; $this->sci->assign('pr_id' , $pr_id);
		
		//get promo
		$this->db->where('pr_id' , $pr_id);
		$res = $this->db->get('promo');
		$promo = $res->row_array();
		$this->sci->assign('promo' , $promo);
		if($promo) {
			$promo_end_date = $promo['pr_end_promo']; 
			$item_delivered_date = date_future($promo_end_date, "+3 month", "%Y-%m-%d %H:%i:%s" );
			$this->sci->assign('item_delivered_date' , $item_delivered_date);
		}

		//get shipping address, billing address and shipping method
		$shipping_address = $this->mod_checkout->get_member_address($shipping_address_id);
		$this->sci->assign('shipping_address' , $shipping_address);
		$billing_address = $this->mod_checkout->get_member_address($billing_address_id);
		$this->sci->assign('billing_address' , $billing_address);
		$shipping_method = $this->mod_checkout->get_shipping_method_by_id($shipping_method_id);
		$this->sci->assign('shipping_method' , $shipping_method);

		//get voucher
		$voucher_nominal = 0;
		if($voucher_code != '') {
			$return = $this->mod_checkout->get_voucher_by_code($voucher_code, $this->userinfo['m_id'], $cart['cart_subtotal'] );
			if($return['type'] == 'Normal') {
				$voucher_nominal = $return['data']['v_nominal'];
			} elseif($return['type'] == 'Promo') {
				$voucher_nominal = $return['data']['vs_nominal'];
			}
		}
		$this->sci->assign('voucher_nominal' , $voucher_nominal);

		//calculate shipping_price
		$shipping_price = $this->mod_checkout->calculate_shipping_price_by_address($shipping_address_id, $shipping_method_id, $total_weight,  $cart['pr_id']);
		$this->sci->assign('shipping_price' , $shipping_price);

		//calculate total
		$grandtotal = $this->_count_grandtotal($cart['cart_subtotal'], $voucher_nominal, $shipping_price);
		$total_payout = $this->_count_totalpayout($grandtotal, $credit_amount);
		
		if($total_payout > 0) {
			$total_payout = $this->_count_add_unique($total_payout, $unique);
		} else {
			$this->sci->assign('zero_total' , 'Yes');
		}
		
		
		$this->sci->assign('grandtotal' , $grandtotal);
		$this->sci->assign('total_payout' , $total_payout);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('do_submit', '', 'trim');
		$this->form_validation->set_rules('i_agree', 'Agreement Confirmation', 'required|trim');
		if($this->form_validation->run() == FALSE) {
			$this->_load_topbar();
			$this->sci->da('checkout_verify.htm');
		} else {
			$this->submit_checkout();
		}
	}

	function submit_checkout(){
		$m_id = $this->userinfo['m_id'];
		$zero_total = 'No';

		//$cartobj = $this->_get_cart_data();
		//$cart = $cartobj['cart_data'];
		//$cart_item = $cartobj['cart_data']['items'];
		//$cart_subtotal = $cartobj['cart_final_payout'];
		//$total_weight = $cartobj['cart_total_weight'];
		//$brand = $cartobj['cart_brand'];
		//$br_id = $cartobj['cart_br_id'];
		//$brp_id = $cartobj['cart_brp_id'];
		//$free_shipping = $cartobj['cart_free_shipping'];

		$cart = $this->icart->get_cart();
		if(sizeof($cart['items']) == 0) {
			redirect( $this->mod_url.'view_checkout');
		}
		$cart_item = $cart['items'];
		$cart_subtotal = $cart['cart_subtotal'];
		$total_weight =  $cart['total_weight'];
		$brand = $cart['brand'];
		$free_shipping = $cart['free_shipping'];
		$br_id = $cart['br_id'];
		$pr_id = $cart['pr_id'];

		$shipping_address_id = @$_SESSION['shipping_address_id'];
		$billing_address_id = @$_SESSION['billing_address_id'];
		$shipping_method_id = @$_SESSION['shipping_method_id'];
		$credit_amount = @$_SESSION['credit_amount'];
		$voucher_code = @$_SESSION['submit_voucher_code'];
		$send_as_gift = @$_SESSION['send_as_gift'];
		$unique = @$_SESSION['unique'];
		//$pr_id = @$_SESSION['pr_id'];


		if($voucher_code == '') { $voucher_nominal = 0; $v_id = 0;}
		if($credit_amount == '') { $credit_amount = 0; }


		$this->db->trans_start();
//----transaction start

		//get voucher
		$voucher_nominal = 0;
		if($voucher_code != '') {
			$return = $this->mod_checkout->get_voucher_by_code($voucher_code, $this->userinfo['m_id'], $cart_subtotal);
			if($return['type'] == 'Normal') {
				$voucher_nominal = $return['data']['v_nominal'];
			} elseif($return['type'] == 'Promo') {
				$voucher_nominal = $return['data']['vs_nominal'];
			}
		}

		$shipping_address = $this->mod_checkout->get_member_address($shipping_address_id);
		$billing_address = $this->mod_checkout->get_member_address($billing_address_id);
		$shipping_method = $this->mod_checkout->get_shipping_method_by_id($shipping_method_id);
		$shipping_price = $this->mod_checkout->calculate_shipping_price_by_address($shipping_address_id, $shipping_method_id, $total_weight, $pr_id);
		$payment_method = $this->mod_checkout->get_payment_method_by_id('1');
 
		$grandtotal = $this->_count_grandtotal($cart_subtotal, $voucher_nominal, $shipping_price);
		$total_payout = $this->_count_totalpayout($grandtotal, $credit_amount);
		
		if($total_payout > 0) {
			$total_payout = $this->_count_add_unique($total_payout, $unique);
		} else {
			$zero_total = 'Yes';
			$unique = 0;
		}

		//if used voucher, set voucher as used
		if($voucher_code != '') {
			$return = $this->mod_checkout->get_voucher_by_code($voucher_code, $this->userinfo['m_id'], $cart_subtotal );
			if($return['type'] == 'Normal') {
				$voucher_nominal = $return['data']['v_nominal'];
				$this->db->where('v_id' , $return['data']['v_id']);
				$this->db->set('m_id' , $m_id);
				$this->db->set('v_used_time' , 'NOW()', FALSE);
				$this->db->set('v_used' , 'Yes');
				$this->db->set('v_status' , 'Used');
				$this->db->update('voucher');
				$v_id = $return['data']['v_id'];
			} elseif($return['type'] == 'Promo') {
				$voucher_set = $return['data'];
				$this->db->set('m_id' , $m_id);
				$this->db->set('vs_id' , $voucher_set['vs_id']);
				$this->db->set('v_type' , $voucher_set['vs_type']);
				$this->db->set('v_code' , $voucher_set['vs_code']);
				$this->db->set('v_nominal' , $voucher_set['vs_nominal']);
				$this->db->set('v_used' , 'Yes');
				$this->db->set('v_status' , 'Used');
				$this->db->set('v_used_time' , 'NOW()', FALSE);
				$this->db->set('v_entry' , 'NOW()', FALSE);
				$this->db->insert('voucher');
				$v_id = $this->db->insert_id();
			}

			$this->db->set('trans_v_id' , $v_id);
			$this->db->set('trans_v_type' , $return['type']);
			$this->db->set('trans_v_nominal' , $voucher_nominal);
		}

		//start setting database for transaction
		$this->db->set('m_id' , $m_id); 
		$this->db->set('trans_shipping_name' , $shipping_address['madr_name']);
		$this->db->set('trans_shipping_address' , $shipping_address['madr_address']);
		$this->db->set('trans_shipping_phone' , $shipping_address['madr_phone']);
		$this->db->set('trans_shipping_zipcode' , $shipping_address['madr_zipcode']);
		$this->db->set('trans_shipping_province' , $shipping_address['ap_name']);
		$this->db->set('trans_shipping_city' , $shipping_address['ac_name']);
		$this->db->set('trans_shipping_id' , $shipping_address['madr_id']); 
		$this->db->set('trans_billing_name' , $billing_address['madr_name']);
		$this->db->set('trans_billing_address' , $billing_address['madr_address']);
		$this->db->set('trans_billing_phone' , $billing_address['madr_phone']);
		$this->db->set('trans_billing_zipcode' , $billing_address['madr_zipcode']);
		$this->db->set('trans_billing_province' , $billing_address['ap_name']);
		$this->db->set('trans_billing_city' , $billing_address['ac_name']);
		$this->db->set('trans_billing_id' , $billing_address['madr_id']);

		$this->db->set('trans_shipping_method_id' , $shipping_method['sm_id']);
		$this->db->set('trans_shipping_method' , $shipping_method['sm_name']);

		$this->db->set('trans_pm_id' , $payment_method['pm_id']);
		$this->db->set('trans_payment_method' , $payment_method['pm_name']);
		$this->db->set('trans_shipping_price' , $shipping_price);
		$this->db->set('trans_free_shipping' , $free_shipping); 
		$this->db->set('pr_id' , $pr_id);
		$this->db->set('trans_saldo_used' , $credit_amount);
		$this->db->set('trans_cart_total' , $cart_subtotal);
		$this->db->set('trans_as_gift' , $send_as_gift);

		$this->db->set('trans_cart_total' , $cart_subtotal);

		$this->db->set('trans_grandtotal' , $grandtotal);
		$this->db->set('trans_unique' , $unique);
		$this->db->set('trans_payout' , $total_payout);
		$this->db->set('trans_entry' , date('Y-m-d H:i:s'));
		
		if($zero_total == 'Yes') {
			$this->db->set('trans_payment_status' , 'Paid');
			$this->db->set('trans_paid_date' , 'NOW()', FALSE);
			$this->db->set('trans_confirm_date' , 'NOW()', FALSE);
			$this->db->set('trans_confirm_entry' , 'NOW()', FALSE);
		}

		$ok = $this->db->insert('transaction');
		$insert_id = $this->db->insert_id();
 

		//cut saldo from member
		if($credit_amount > 0) {
			$saldo = $this->_get_saldo();
			$this->db->where('m_id' , $m_id);
			$this->db->set('m_saldo' , $saldo - $credit_amount);
			$this->db->update('member');	
		}
		

		$total_weight = 0;
		foreach($cart_item as $k=>$tmp){
			$product = $this->mod_product->get_by_id($tmp['p_id']);

			$pq_id = isset($tmp['pq_id'])?$tmp['pq_id']:0;

			$this->db->set('trans_id' , $insert_id);
			$this->db->set('m_id' , $m_id);
			$this->db->set('p_id' , $tmp['p_id']);
			$this->db->set('pq_id' , $pq_id);
			$this->db->set('transd_type' , $product['p_type']);
			$this->db->set('transd_quantity' , $tmp['cart_quantity']);
			$this->db->set('transd_weight' , $tmp['p_weight']);
			$this->db->set('transd_price' , $tmp['cart_price']);
			$this->db->set('transd_subtotal' , $tmp['cart_subtotal']);

			$option[] = array('pq_id', $pq_id);
			$option[] = array('size', $tmp['pq_size']);
			$this->db->set('transd_option' , serialize($option));
			$this->db->set('transd_entry' , date('Y-m-d H:i:s'));

			$ok = $this->db->insert('transaction_detail');
			$transd_insert_id = $this->db->insert_id();

			$total_weight += $tmp['p_weight'];
		} 

		//total weight
		$this->db->where('trans_id' , $insert_id);
		$this->db->set('trans_shipping_weight' , $total_weight);
		$ok = $this->db->update('transaction');



	//----transaction complete
		$ok = $this->db->trans_complete();
		if($ok) {
			$ok = $this->icart->clear();
			$ok = $this->send_checkout_email($insert_id);
			redirect(site_url()."checkout/checkout_success/$insert_id");
		} else {
			redirect(site_url()."checkout/checkout_failed");
		}
	}

	function checkout_success($trans_id=0){
		//get trans
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();
		$this->sci->assign('transaction' , $transaction);
		
		//get bank account numbers
		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		//get checkout_success_disclaimer
		$this->db->where('c_code' , 'checkout-success');
		$res = $this->db->get('content');
		$checkout_success_content = $res->row_array();
		$this->sci->assign('checkout_success_content' , $checkout_success_content);

		$this->sci->assign('trans_id' , $trans_id);

		$this->sci->da('checkout_success.htm');
	}

	function checkout_failed(){
		$this->sci->da('checkout_failed.htm');
	}

	function send_checkout_email($trans_id) {
		//get bank account numbers
		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		$this->db->where('trans_id' , $trans_id);
		$this->db->where('trans_status' , 'Active');
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();

		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$this->db->where('transd_status' , 'Active');
		$res = $this->db->get('transaction_detail');
		$transaction_detail = $res->result_array();

		$total_cart = 0;
		foreach($transaction_detail as $k=>$tmp) {
			$option = unserialize($tmp['transd_option']);
			//print_r($option);
			foreach($option as $k2=>$tmp2) {
				$transaction_detail[$k][$tmp2[0]] = $tmp2[1];
			}
			$total_cart += $tmp['transd_subtotal'];
		}
		$this->sci->assign('total_cart' , $total_cart);
		$this->sci->assign('transaction' , $transaction);
		$this->sci->assign('transaction_detail' , $transaction_detail);
		$this->sci->assign('trans_id' , $trans_id);

		$html = $this->sci->fetch('checkout/email_checkout.htm');

		//send email
		$email = $this->userinfo['m_email'];

		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($email);
		$this->email->subject( 'Thank You For Shopping With Us' );
		$this->email->message($html);

		$ok = $this->email->send();
		return $ok;
	}

	function view_email($trans_id=0) {
		//get bank account numbers
		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		$this->db->where('trans_id' , $trans_id);
		$this->db->where('trans_status' , 'Active');
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();

		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$this->db->where('transd_status' , 'Active');
		$res = $this->db->get('transaction_detail');
		$transaction_detail = $res->result_array();

		$total_cart = 0;
		foreach($transaction_detail as $k=>$tmp) {
			$option = unserialize($tmp['transd_option']);
			//print_r($option);
			foreach($option as $k2=>$tmp2) {
				$transaction_detail[$k][$tmp2[0]] = $tmp2[1];
			}
			$total_cart += $tmp['transd_subtotal'];
		}
		$this->sci->assign('total_cart' , $total_cart);
		$this->sci->assign('transaction' , $transaction);
		$this->sci->assign('transaction_detail' , $transaction_detail);

		$html = $this->sci->fetch('checkout/email_checkout.htm');
		echo $html;

		//send email
		$email = $this->userinfo['m_email'];

		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($email);
		$this->email->subject( 'Thank You For Shopping With Us' );
		$this->email->message($html);

		$ok = $this->email->send();
		return $ok;
	}


	function _generate_referral_voucher() {
		$batch_number = '000';

		//get voucher with type referral
		$this->db->where('v_type' , 'Referral');
		$res = $this->db->get('voucher');
		$last_voucher = $res->row_array();
		if(!$last_voucher){
			$code = str_repeat("0" , 8).'1';
		} else {
			$last_code = $last_voucher['v_code'];
			$code = substr($last_code , 3 , -4);
			$code = $code + 1;
			$code = $code.'';
			$code = str_repeat("0" , (9-strlen($code))).$code;
		}
		$pin = rand(111, 999);
		$code = $this->mod_global->generate_lun($code);

		$voucher = $batch_number.$code.$pin;
		return $voucher;
	}

	function aref() {
		$userinfo = $this->session->get_userinfo('member');
		$this->load->model('mod_voucher');
		$this->mod_voucher->assign_voucher_referral($userinfo['m_id']);
	}



}
