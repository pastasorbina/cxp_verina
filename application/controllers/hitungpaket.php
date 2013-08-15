<?php

class Hitungpaket extends MY_Controller {
	
	var $branch;
	
	function __construct() {
		parent::__construct();
		
		$this->load->library('Itemcart');
		$this->load->model('mod_global');
		
		$this->sci->init('front');
		$this->_init();
		
		$b_id = $this->branch_id;
		$this->db->where('b_id' , $b_id);
		$res = $this->db->get('branch');
		$this->branch = $res->row_array();
	}
	
	function index() {
		// Get branch data
		$branch = $this->mod_global->get_options('branch' , 'b_code' , 'b_name' , "b_status = 'Active'");
		$this->sci->assign('branch' , $branch);
		// Load item category
		$category = $this->mod_global->get_options('item_category' , 'ic_id' , 'ic_name' , "ic_status = 'Active'" , 'ic_name');
		$this->sci->assign('category' , $category);
		
		//print $this->branch['b_code'];
		$this->sci->assign('resto_id' ,  $this->branch['b_code']);
		
		$this->sci->da('index.htm');
	}
	
	function clear() {
		$this->sci->d('clear.htm');
	}
	
	function get_subcat() {
		$ic_id = $this->input->post('ic_id');
		
		$subcat = $this->mod_global->get_options('item_subcategory' , 'isc_id' , 'isc_name' , "isc_status = 'Active' AND ic_id = '$ic_id'" , 'isc_name');
		$this->sci->assign('sub_category' , $subcat);
		
		$this->sci->d('get_subcat.htm');
	}

	function get_item() {
		$isc_id = $this->input->post('isc_id');
		//$resto_id = $this->input->post('resto_id');
		$resto_id = $this->branch['b_code'];
		
		if (($resto_id != 'ancol')&&($resto_id != 'alsut')) exit;
		
		$res = $this->db->
			where('i_visible_' . $resto_id, '1')->
			where('i_status' , 'Active')->
			where('isc_id' , $isc_id)->
			get('item');
		
		$item = array();
		foreach($res->result() as $row) {
			$item[$row->i_id] = "{$row->i_name}"; 
		}
		$this->sci->assign('item' , $item);
		
		$this->sci->d('get_item.htm');
	}
	
	function get_item_detail() {
		$i_id = $this->input->post('i_id');
		
		$res = $this->db->
			where('i_id' , $i_id)->
			where('i_status' , 'Active')->
			get('item');
			//print_r($res->row_array());
		$this->sci->assign('item' , $res->row_array());
		
		$this->sci->d('get_item_detail.htm');
	}
	
	function add_item() {
		$i_id = $this->input->post('i_id');
		$quantity = $this->input->post('quantity');
		
		if ($quantity > 0) {
			$this->itemcart->add_to_cart($i_id , $quantity);
			$this->itemcart->save();
		}
	}
	
	function reload_calculation() {
		$s_day = $this->input->post('s_day');
		$s_person = $this->input->post('s_person');
		//$resto_id = $this->input->post('resto_id');
		$resto_id = $this->branch['b_code'];
		
		
		$cart = $this->itemcart->cart;
		$ncart = array();
		$cooking_charge = 0;
		$subtotal = 0;
		$tax = 0;
		$total = 0;
		
		// Add nasi paket / person
		$nasipaket = new stdClass;
		$nasipaket->id = 131;
		$nasipaket->quantity = $s_person;
		$nasipaket->uniq = 131;
		
		$cart[] = $nasipaket;
		
		foreach($cart as $c) {
			$res = $this->db->
				where('i_id' , $c->id)->
				where('i_status' , 'Active')->
				get('item');
			
			if ($row = $res->row_array()) {
				// Get used price
				$row['used_price'] = $row['i_sale_price_' . $resto_id];
				
				// Set Quantity
				$row['quantity'] = $c->quantity;
				
				// Uniqid
				$row['uniqid'] = $c->uniq;
				
				// Count discount
				if ($row['i_disc_' . $resto_id] == $s_day) {
					$row['disc'] = true;
					$row['subtotal'] = $row['used_price'] / 2 * $c->quantity;
				}
				else {
					$row['disc'] = false;
					$row['subtotal'] = $row['used_price'] * $c->quantity;
				}
				
				$subtotal += $row['subtotal'];
				$cooking_charge += $row['i_cooking_charge'] * $c->quantity;
				
			}
			$ncart[] = $row;
		}
		
		$tax = $subtotal * 0.1;
		$total = $subtotal + $tax;
		
		// set Cooking charge minimum
		if ($cooking_charge < 12000 && $cooking_charge > 0) $cooking_charge = 12000;
		
		$this->sci->assign('cart' , $ncart);
		$this->sci->assign('cooking_charge' , $cooking_charge);
		$this->sci->assign('tax' , $tax);
		$this->sci->assign('subtotal' , $subtotal);
		$this->sci->assign('total' , $total);
		$this->sci->assign('person' , $s_person);
		$this->sci->assign('totalperperson' , $total / $s_person);
		$this->sci->d('reload_calculation.htm');
	}
	
	function print_data($s_person = 0, $s_day = 0, $resto_id = 0) {
		$this->sci->assign('s_person' , $s_person);
		$this->sci->assign('s_day' , $s_day);
		$resto_id = $this->branch['b_code'];
		$this->sci->assign('resto_id' , $resto_id);
		
		// Hari
		$dday[0] = "--";
		$dday[1] = "Senin";
		$dday[2] = "Selasa";
		$dday[3] = "Rabu";
		$dday[4] = "Kamis";
		$dday[5] = "Jumat";
		$dday[6] = "Sabtu";
		$dday[7] = "Minggu";
		$this->sci->assign('dday' , $dday);				

		$cart = $this->itemcart->cart;
		$ncart = array();
		$cooking_charge = 0;
		$subtotal = 0;
		$tax = 0;
		$total = 0;
		
		// Add nasi paket / person
		$nasipaket = new stdClass;
		$nasipaket->id = 131;
		$nasipaket->quantity = $s_person;
		$nasipaket->uniq = 131;
		
		$cart[] = $nasipaket;
		
		foreach($cart as $c) {
			$res = $this->db->
				where('i_id' , $c->id)->
				where('i_status' , 'Active')->
				get('item');
			
			if ($row = $res->row_array()) {
				// Get used price
				$row['used_price'] = $row['i_sale_price_' . $resto_id];
				
				// Set Quantity
				$row['quantity'] = $c->quantity;
				
				// Uniqid
				$row['uniqid'] = $c->uniq;
				
				// Count discount
				if ($row['i_disc_' . $resto_id] == $s_day) {
					$row['disc'] = true;
					$row['subtotal'] = $row['used_price'] / 2 * $c->quantity;
				}
				else {
					$row['disc'] = false;
					$row['subtotal'] = $row['used_price'] * $c->quantity;
				}
				
				$subtotal += $row['subtotal'];
				$cooking_charge += $row['i_cooking_charge'] * $c->quantity;
				
			}
			$ncart[] = $row;
		}
		
		$tax = $subtotal * 0.1;
		$total = $subtotal + $tax;
		
		// set Cooking charge minimum
		if ($cooking_charge < 12000 && $cooking_charge > 0) $cooking_charge = 12000;
		
		$this->sci->assign('cart' , $ncart);
		$this->sci->assign('cooking_charge' , $cooking_charge);
		$this->sci->assign('tax' , $tax);
		$this->sci->assign('subtotal' , $subtotal);
		$this->sci->assign('total' , $total);
		$this->sci->d('print_data.htm');
	}	

	function email_data($s_person = 0, $s_day = 0, $resto_id = 0) {
		$s_person = $this->input->post('s_person');
		$s_day = $this->input->post('s_day');
		//$resto_id = $this->input->post('resto_id');
		$resto_id = $this->branch['b_code'];
		$sim_email = $this->input->post('sim_email');
		
		$this->sci->assign('s_person' , $s_person);
		$this->sci->assign('s_day' , $s_day);
		$this->sci->assign('resto_id' , $resto_id);
		
		$this->load->library('form_validation');
			
		$this->form_validation->set_rules('s_person', 'Person Count', 'required|numeric');
		$this->form_validation->set_rules('s_day', 'Day', 'required|numeric');
		$this->form_validation->set_rules('resto_id', 'Resto Id', 'required');
		$this->form_validation->set_rules('sim_email', 'Email', 'required|valid_email');
			
		if ($this->form_validation->run() == FALSE) {
			$this->sci->assign('validation_errors' , validation_errors());
			$this->sci->da('email_data_failed.htm');
			return;
		}
		
		// Hari
		$dday[0] = "--";
		$dday[1] = "Senin";
		$dday[2] = "Selasa";
		$dday[3] = "Rabu";
		$dday[4] = "Kamis";
		$dday[5] = "Jumat";
		$dday[6] = "Sabtu";
		$dday[7] = "Minggu";
		$this->sci->assign('dday' , $dday);				

		$cart = $this->itemcart->cart;
		$ncart = array();
		$cooking_charge = 0;
		$subtotal = 0;
		$tax = 0;
		$total = 0;
		
		// Add nasi paket / person
		$nasipaket = new stdClass;
		$nasipaket->id = 131;
		$nasipaket->quantity = $s_person;
		$nasipaket->uniq = 131;
		
		$cart[] = $nasipaket;
		
		foreach($cart as $c) {
			$res = $this->db->
				where('i_id' , $c->id)->
				where('i_status' , 'Active')->
				get('item');
			
			if ($row = $res->row_array()) {
				// Get used price
				$row['used_price'] = $row['i_sale_price_' . $resto_id];
				
				// Set Quantity
				$row['quantity'] = $c->quantity;
				
				// Uniqid
				$row['uniqid'] = $c->uniq;
				
				// Count discount
				if ($row['i_disc_' . $resto_id] == $s_day) {
					$row['disc'] = true;
					$row['subtotal'] = $row['used_price'] / 2 * $c->quantity;
				}
				else {
					$row['disc'] = false;
					$row['subtotal'] = $row['used_price'] * $c->quantity;
				}
				
				$subtotal += $row['subtotal'];
				$cooking_charge += $row['i_cooking_charge'] * $c->quantity;
				
			}
			$ncart[] = $row;
		}
		
		$tax = $subtotal * 0.1;
		$total = $subtotal + $tax;
		
		// set Cooking charge minimum
		if ($cooking_charge < 12000 && $cooking_charge > 0) $cooking_charge = 12000;
		
		
		$this->sci->assign('cart' , $ncart);
		$this->sci->assign('cooking_charge' , $cooking_charge);
		$this->sci->assign('tax' , $tax);
		$this->sci->assign('subtotal' , $subtotal);
		$this->sci->assign('total' , $total);
		$email_body = $this->sci->fetch('hitungpaket/email_data.htm');

		$this->load->library('email');
		$config['mailtype'] = 'html';
		$this->email->initialize($config);
		$this->email->from('noreply@bandar-djakarta.com', 'Bandar Djakarta Hitung Paket');
		$this->email->to($sim_email); 
		$this->email->subject('Hitung paket');
		$this->email->message($email_body);	
		$this->email->send();
		
		//echo $this->email->print_debugger();
		
		$this->sci->da('email_data_display.htm');
	}	
	
	function clear_items() {
		$this->itemcart->flush();
		$this->itemcart->save();
	}
	
	function delete_item() {
		$del_id = $this->input->post('del_id');
		
		$this->itemcart->delete_item($del_id);
		$this->itemcart->save();
	}
}
?>