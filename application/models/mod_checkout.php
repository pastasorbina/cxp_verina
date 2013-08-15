<?php

class Mod_checkout extends CI_Model {

	function __construct(){
		parent::__construct();
	}

	function get_member_address($address_id=0) {
		$this->db->join('area_province ap' , 'ap.ap_id = member_address.ap_id' , 'left');
		$this->db->join('area_city ac' , 'ac.ac_id = member_address.ac_id' , 'left');
		$this->db->where('madr_id' , $address_id);
		$this->db->where('madr_status' , 'Active');
		$res = $this->db->get('member_address');
		$address = $res->row_array();
		return $address;
	}

	function get_shipping_method_by_id($sm_id=0) {
		$this->db->where('sm_id' , $sm_id);
		$this->db->where('sm_status' , 'Active');
		$res = $this->db->get('shipping_method');
		$method = $res->row_array();
		return $method;
	}

	function generate_random(){
		$random = rand(10,99);
		return($random);
	}

	function get_voucher_by_code($voucher_code=0, $member_id=0, $voucher_cart_final_payout=0) {
		$return['data'] = array();
		$return['type'] = 'Error';

		$this->db->where('v_code' , $voucher_code);
		$this->db->where('m_id' , $member_id);
		$this->db->where('v_status' , 'New');
		$res = $this->db->get('voucher');
		$voucher = $res->row_array();
		if($voucher) {
			$return['data'] = $voucher;
			$return['type'] = 'Normal';
		} else {
			//if not exist, search through promo
			$this->db->where('vs_code' , $voucher_code);
			$this->db->where('vs_type' , 'Promo');
			$this->db->where('vs_status' , 'Active');
			$res = $this->db->get('voucher_set');
			$voucher_set = $res->row_array();
			if(!$voucher_set OR $voucher) {
				//return FALSE;
			} else {
				$this->db->where('vs_id' , $voucher_set['vs_id']);
				$this->db->where('m_id' , $member_id);
				$this->db->where('v_used' , 'Yes');
				$res = $this->db->get('voucher');
				$voucher_in_set = $res->row_array();
				if($voucher_in_set) {
					//return FALSE;
				} else {
					//check if payout is below the minimum purchase
					$min_purchase = price_format($voucher_set['vs_min_purchase']);
					if($voucher_cart_final_payout < $voucher_set['vs_min_purchase'] ) {
						//return FALSE;
					} else {
						$return['data'] = $voucher_set;
						$return['type'] = 'Promo';
					}
				}

			}
		}
		return $return;
	}

	function get_payment_method_by_id($pm_id) {
		$this->db->where('pm_id' , $pm_id);
		$this->db->where('pm_status' , 'Active');
		$res = $this->db->get('payment_method');
		$payment_method = $res->row_array();
		return $payment_method;
	}

	function calculate_shipping_price($ac_id=0, $sm_id=0, $total_weight=1, $pr_id=0){
		$this->db->where('pr_id' , $pr_id);
		$res = $this->db->get('promo');
		$promo = $res->row_array();
		$free_shipping = 'No';
		$freeship_area = array();
		if($promo) {
			$free_shipping = $promo['pr_free_shipping'];
			$freeship_area_temp = unserialize($promo['pr_freeship_area']);
			//print_r($freeship_area_temp);
			//if(is_array($freeship_area_temp)) {
			//	foreach($freeship_area_temp as $k=>$tmp) {
			//		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
			//		$this->db->where('ac_id' , $tmp);
			//		$res = $this->db->get('area_city');
			//		$city = $res->row_array();
			//		$freeship_area[$k] = $city['ac_id'];
			//	}
			//} 
			$check = 'No';
			
			//kalau brand ini freeshipping, tapi tidak memiliki area, otomatis jadi YES
			if($free_shipping == 'Yes' AND !is_array($freeship_area_temp)) { $check = 'Yes'; }
			
			//lalu dicek lagi, kalo ada area yang matching dengan list freeshippingnya, baru jadi YES
			if(is_array($freeship_area_temp)) {
				foreach($freeship_area_temp as $k=>$tmp) {
					$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
					$this->db->where('ac_id' , $tmp);
					$res = $this->db->get('area_city');
					$city = $res->row_array();
					$freeship_area[$k] = $city['ac_id'];
					if($city['ac_id'] == $ac_id) { $check = 'Yes'; }
				}
			}
		}

		//if area is not in free_shipping area, then no freeshipping
		//if(!in_array($ac_id, $freeship_area)) {
		//	$free_shipping = 'No';
		//}

		if($check == 'No'   ) {
			//TODO: add shipping calculation by weight here
			$this->db->where('ac_id' , $ac_id);
			$this->db->where('sm_id' , $sm_id);
			$this->db->where('sp_disabled' , 'No');
			$res = $this->db->get('shipping_price');
			$sp = $res->row_array();

			$total_weight = ceil($total_weight);
			$modifier = 0;
			if($total_weight < 2) {
				$modifier = 3000;
			}

			if(!$sp){
				return false;
			} else {
				//shipping price modifier
				if($sp['sp_is_fixed_price'] == 'Yes') {
					$price = $sp['sp_price'];
				} else {
					$price = ($total_weight * $sp['sp_price']) + $modifier;
				}
				//calculate insurance tax
				$after_tax = $this->calculate_insurance_tax($price,$sm_id);
				if(!$after_tax) {
					return false;
				} else {
					return $after_tax;
				}
			}
		} else {
			$price = number_format(0,2);
			return $price;
		}

	}

	function calculate_shipping_price_by_address($madr_id=0, $sm_id=0, $total_weight=1, $pr_id=0){
		//get city
		$this->db->where('madr_id' , $madr_id);
		$res = $this->db->get('member_address');
		$address = $res->row_array();
		if($address) {
			$ac_id = $address['ac_id'];
		} else {
			$ac_id = 0;
		}

		$price = $this->calculate_shipping_price($ac_id, $sm_id, $total_weight, $pr_id);
		return $price;
	}

	function calculate_insurance_tax($shipping_price=0, $sm_id=0) {
		//get cart data
		$this->load->library('icart');
		$cart = $this->icart->get_cart();
		if(!$cart){
			$cart_subtotal = 0; // TODO: ini harusnya gak nol nih
		} else {
			$cart_subtotal = $cart['cart_subtotal'];
		}

		//get shipping_method
		$this->db->where('sm_id' , $sm_id);
		$res = $this->db->get('shipping_method');
		$method = $res->row_array();
		if(!$method) {
			return false;
		} else {
			$tax = $method['sm_insurance_tax'];
			$after_tax = $shipping_price + ($cart_subtotal * $tax / 100);
			return $after_tax;
		}
	}


}

?>
