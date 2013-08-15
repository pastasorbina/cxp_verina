<?php
class Transaction_monitor extends MY_Controller {

	var $mod_title = 'Transaction Monitor';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'transaction';
	var $id_field = 'trans_id';
	var $status_field = 'trans_status';
	var $entry_field = 'trans_entry';
	var $stamtrans_field = 'trans_stamp';
	var $deletion_field = 'trans_deletion';
	var $order_field = 'trans_entry';

	var $author_field = 'trans_author';
	var $editor_field = 'trans_editor';

	var $search_in = array('trans_id', 'm_firstname','m_lastname','m_email');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
		$this->userinfo = $this->session->get_userinfo();
		$this->load->model('mod_transaction');
	}
	
	function _load_sidebar() {
		$html = $this->sci->fetch('admin/transaction_monitor/sidebar.htm');
		$this->sci->assign('sidebar' , $html);
	}

	function index() {
		redirect($this->mod_url.'unconfirmed_one_day' );
	}
	
	
	function unconfirmed_one_day($pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		$this->_load_sidebar();
		
		$today = date('Y-m-d H:i:s');
		$this->sci->assign('today' , $today);
		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			
			$this->db->join('member m' , 'm.m_id = transaction.m_id' , 'left');
			$this->db->where('trans_status' , 'Active');
			$this->db->where('trans_payment_status = ' , "Unconfirmed");
			
			
			//print $targetdate;
			//$this->db->where('trans_entry' , $targetdate);
			$this->db->from('transaction'); 
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('transaction');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		
		foreach($maindata as $k=>$tmp) {
			$expiry = strtotime($tmp['trans_entry'] . " +1 Day");
			$expiry = mdate("%Y-%m-%d %H:%i:%s", $expiry);
			$maindata[$k]['expiry'] = $expiry;
			
			$diff = datediff(date('Y-m-d H:i:s'), $expiry );
			$maindata[$k]['diff'] = $diff;
			
			$this->db->where('trans_id' , $tmp['trans_id']);
			$this->db->where('transd_status' , 'Active');
			$res = $this->db->get('transaction_detail');
			$trans_detail = $res->result_array();
			
		}
		
		
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('unconfirmed_one_day.htm');
	}


	function enum_setting($maindata=array()) {

		return $maindata;
	}

	function join_setting() {
		
	}

	function where_setting() { 
	}

	function validation_setting() {
		$this->form_validation->set_rules('trans_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('trans_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('pc_id', 'Category', 'trim|xss_clean');
		$this->form_validation->set_rules('pt_id', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('br_id', 'Brand', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_order', 'Ordering', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_price', 'Price', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_discount_price', 'Discounted Price', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_weight', 'Weight', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_description', 'Description', 'trim');
	}

	function database_setter() {
		$this->db->set('pc_id' , $this->input->post('pc_id'));
		$this->db->set('pt_id' , $this->input->post('pt_id'));
		$this->db->set('br_id' , $this->input->post('br_id'));
		$this->db->set('trans_order' , $this->input->post('trans_order'));
		$this->db->set('trans_name' , $this->input->post('trans_name') );
		$this->db->set('trans_code' , $this->input->post('trans_code') );
		$this->db->set('trans_price' , $this->input->post('trans_price') );
		$this->db->set('trans_discount_price' , $this->input->post('trans_discount_price') );
		$this->db->set('trans_description' , $this->input->post('trans_description') );
		$this->db->set('trans_weight' , $this->input->post('trans_weight') );

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['trans_image1']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image1');
			$this->db->set('trans_image1' , $filename);
		}
		if($_FILES['trans_image2']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image2');
			$this->db->set('trans_image2' , $filename);
		}
		if($_FILES['trans_image3']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image3');
			$this->db->set('trans_image3' , $filename);
		}
		if($_FILES['trans_image4']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image4');
			$this->db->set('trans_image4' , $filename);
		}
		if($_FILES['trans_image5']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image5');
			$this->db->set('trans_image5' , $filename);
		}
	}


	function pre_add_edit() {

	}

	function pre_add() {
	}

	function pre_edit($id=0) {
		$this->db->where('pq_status' , 'Active');
		$this->db->where('trans_id' , $id);
		$res = $this->db->get('product_quantity');
		$product_quantity = $res->result_array();
		$this->sci->assign('product_quantity' , $product_quantity);
	}


	function manual_confirm($trans_id) {
		//get banks
		$this->db->where('bank_status' , 'Active');
		$res = $this->db->get('bank');
		$banks = $res->result_array();
		$this->sci->assign('banks' , $banks);

		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();

		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		$this->db->where('pm_status' , 'Active');
		$res = $this->db->get('payment_method');
		$payment_method = $res->result_array();
		$this->sci->assign('payment_method' , $payment_method);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('payment_method', 'Payment Method', 'trim|xss_clean|required');
		$this->form_validation->set_rules('bank_account', 'Bank Account', 'trim|xss_clean|required');
		$this->form_validation->set_rules('date', 'Payment Date', 'trim|xss_clean|required');
		$this->form_validation->set_rules('paid_amount', 'Paid Amount', 'trim|xss_clean|required|numeric');
		$this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|xss_clean|required');
		if($this->input->post('bank_name') == "Other") {
			$this->form_validation->set_rules('bank_name_alt', 'Bank Name', 'trim|xss_clean|required');
		}
		$this->form_validation->set_rules('bank_account_no', 'Account No.', 'trim|xss_clean|required');
		$this->form_validation->set_rules('bank_account_holder', 'Account Name', 'trim|xss_clean|required');

		if($this->form_validation->run() == FALSE) {
			$this->sci->assign('transaction' , $transaction);
			$this->sci->da('confirmation_form.htm');
		} else {
			$confirmation_date = $this->input->post('date');
			$pm_id = $this->input->post('payment_method');
			$ba_id = $this->input->post('bank_account');

			//get payment method detail
			$this->db->where('pm_id' , $pm_id );
			$res = $this->db->get('payment_method');
			$payment_method = $res->row_array();
			$payment_method = $payment_method['pm_name'];

			//get bank account detail
			$this->db->where('ba_id' , $ba_id);
			$res = $this->db->get('bank_account');
			$bank_account = $res->row_array();
			$bank_account = $bank_account['ba_bank_name'].', '.$bank_account['ba_name'].', acc '.$bank_account['ba_account_no'].' a/n '.$bank_account['ba_account_holder'];

			$this->db->trans_start();
				$this->db->set('transc_is_manual' , 'Yes');
				$this->db->set('transc_manual_u_id' , $this->userinfo['u_id'] );

				$this->db->set('trans_id' , $trans_id);
				$this->db->set('pm_id' , $this->input->post('payment_method') );
				$this->db->set('ba_id' , $this->input->post('bank_account') );
				$this->db->set('transc_bank_account' , $bank_account);
				$this->db->set('transc_payment_method' , $payment_method);
				$this->db->set('transc_date' , $confirmation_date );
				$this->db->set('transc_paid_amount' , $this->input->post('paid_amount') );
				$this->db->set('transc_from_bank_name' , $this->input->post('bank_name') );
				if($this->input->post('bank_name') == "Other" ) {
					$this->db->set('transc_from_bank_name_alt' , $this->input->post('bank_name_alt') );
				}
				$this->db->set('transc_from_account_no' , $this->input->post('bank_account_no') );
				$this->db->set('transc_from_account_holder' , $this->input->post('bank_account_holder') );
				$this->db->set('transc_payment_due' , $transaction['trans_payout'] );
				$this->db->set('m_id' , $transaction['m_id'] );
				$this->db->set('transc_entry' , date('Y-m-d H:i:s') );
				$ok = $this->db->insert('transaction_confirmation');

				//update transaction status
				$this->db->set('trans_pm_id' , $pm_id);
				$this->db->set('trans_ba_id' , $ba_id);
				$this->db->set('trans_confirm_date' , $confirmation_date);
				$this->db->set('trans_confirm_entry' , 'NOW()', false);
				$this->db->set('trans_payment_method' , $payment_method);
				$this->db->set('trans_payment_account' , $bank_account);
				$this->db->set('trans_payment_status' , 'Confirmed');
				$this->db->where('trans_id' , $trans_id);
				$this->db->update('transaction');

			$this->db->trans_complete();

			if($this->db->trans_status() === FALSE) {
				$this->session->set_confirm(0, 'cannot confirm your payment, please try again later');
			}else {
				$this->session->set_confirm(1, 'payment confirmed');
			}
			redirect($this->session->get_bread('list'));
		}
	}


	function edit_confirm($transc_id) {
		$this->db->where('transc_id' , $transc_id);
		$res = $this->db->get('transaction_confirmation');
		$trans_confirmation = $res->row_array();
		$this->sci->assign('trans_confirmation' , $trans_confirmation);

		$this->db->where('trans_id' , $trans_confirmation['trans_id']);
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();
		$this->sci->assign('transaction' , $transaction);

		//get banks
		$this->db->where('bank_status' , 'Active');
		$res = $this->db->get('bank');
		$banks = $res->result_array();
		$this->sci->assign('banks' , $banks);

		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		$this->db->where('pm_status' , 'Active');
		$res = $this->db->get('payment_method');
		$payment_method = $res->result_array();
		$this->sci->assign('payment_method' , $payment_method);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('payment_method', 'Payment Method', 'trim|xss_clean|required');
		$this->form_validation->set_rules('bank_account', 'Bank Account', 'trim|xss_clean|required');
		$this->form_validation->set_rules('date', 'Payment Date', 'trim|xss_clean|required');
		$this->form_validation->set_rules('paid_amount', 'Paid Amount', 'trim|xss_clean|required|numeric');
		$this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|xss_clean|required');
		if($this->input->post('bank_name') == "Other") {
			$this->form_validation->set_rules('bank_name_alt', 'Bank Name', 'trim|xss_clean|required');
		}
		$this->form_validation->set_rules('bank_account_no', 'Account No.', 'trim|xss_clean|required');
		$this->form_validation->set_rules('bank_account_holder', 'Account Name', 'trim|xss_clean|required');

		if($this->form_validation->run() == FALSE) {

			$this->sci->da('edit_confirmation.htm');
		} else {
			$confirmation_date = $this->input->post('date');
			$pm_id = $this->input->post('payment_method');
			$ba_id = $this->input->post('bank_account');

			//get payment method detail
			$this->db->where('pm_id' , $pm_id );
			$res = $this->db->get('payment_method');
			$payment_method = $res->row_array();
			$payment_method = $payment_method['pm_name'];

			//get bank account detail
			$this->db->where('ba_id' , $ba_id);
			$res = $this->db->get('bank_account');
			$bank_account = $res->row_array();
			$bank_account = $bank_account['ba_bank_name'].', '.$bank_account['ba_name'].', acc '.$bank_account['ba_account_no'].' a/n '.$bank_account['ba_account_holder'];

			$this->db->trans_start();
				$this->db->set('transc_is_manual' , 'Yes');
				$this->db->set('transc_manual_u_id' , $this->userinfo['u_id'] );
				$this->db->where('transc_id' , $transc_id);
				$this->db->set('pm_id' , $this->input->post('payment_method') );
				$this->db->set('ba_id' , $this->input->post('bank_account') );
				$this->db->set('transc_bank_account' , $bank_account);
				$this->db->set('transc_payment_method' , $payment_method);
				$this->db->set('transc_date' , $confirmation_date );
				$this->db->set('transc_paid_amount' , $this->input->post('paid_amount') );
				$this->db->set('transc_from_bank_name' , $this->input->post('bank_name') );
				if($this->input->post('bank_name') == "Other" ) {
					$this->db->set('transc_from_bank_name_alt' , $this->input->post('bank_name_alt') );
				}
				$this->db->set('transc_from_account_no' , $this->input->post('bank_account_no') );
				$this->db->set('transc_from_account_holder' , $this->input->post('bank_account_holder') );
				$this->db->set('transc_payment_due' , $transaction['trans_payout'] );
				$this->db->set('m_id' , $transaction['m_id'] );
				$ok = $this->db->update('transaction_confirmation');

				//update transaction status
				$this->db->set('trans_pm_id' , $pm_id);
				$this->db->set('trans_ba_id' , $ba_id);
				$this->db->set('trans_confirm_date' , $confirmation_date);
				$this->db->set('trans_confirm_entry' , 'NOW()', false);
				$this->db->set('trans_payment_method' , $payment_method);
				$this->db->set('trans_payment_account' , $bank_account);
				$this->db->set('trans_payment_status' , 'Confirmed');
				$this->db->where('trans_id' , $trans_confirmation['trans_id']);
				$this->db->update('transaction');

			$this->db->trans_complete();

			if($this->db->trans_status() === FALSE) {
				$this->session->set_confirm(0, 'cannot confirm your payment, please try again later');
			}else {
				$this->session->set_confirm(1, 'payment confirmed');
			}
			redirect($this->session->get_bread('list'));
		}
	}


	function view($trans_id=0) {
		//$this->session->set_bread('list');
		$this->db->join('brand br' , 'br.br_id = transaction.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();

		//get detail
		$this->db->join('product_quantity pq' , 'pq.pq_id = transaction_detail.pq_id' , 'left');
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();

		//get confirmation
		$this->db->join('user' , 'user.u_id = transaction_confirmation.transc_manual_u_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_confirmation');
		$trans_confirmation = $res->row_array();

		$this->sci->assign('trans' , $trans);
		$this->sci->assign('trans_detail' , $trans_detail);
		$this->sci->assign('trans_confirmation' , $trans_confirmation);

		$this->sci->da('view.htm');
	}


	function change_to_paid($trans_id=0) {
		$this->db->where('trans_id' , $trans_id);
		$this->db->set('trans_payment_status' , 'Paid');
		$ok = $this->db->update('transaction');
		if($ok) {
			$this->session->set_confirm(1);
		} else {
			$this->session->set_confirm(0);
		}
		redirect( $this->session->get_bread('list') );
	}

	function status_change($trans_id=0, $from="", $to="") {
		if($trans_id !=0 && $to != "" && $from!=""){

			$this->mod_transaction->status_change_history($from,$to);
			$this->db->where('trans_id' , $trans_id);
			$this->db->set('trans_payment_status' , $to);
			$ok = $this->db->update('transaction');
			if($ok) {
				$this->session->set_confirm(1);
			} else {
				$this->session->set_confirm(0);
			}
			redirect( $this->session->get_bread('list') );
		}
	}
	
	
	
	
	function edit_address($trans_id=0) {
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();
		$this->sci->assign('trans' , $trans);
		$this->load->library('form_validation');
		$this->form_validation->set_rules('trans_shipping_name', 'Shipping Name', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_shipping_address', 'Shipping Address', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_shipping_phone', 'Shipping Phone', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_shipping_province', 'Shipping Province', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_shipping_city', 'Shipping City', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_shipping_zipcode', 'Shipping Zipcode', 'trim|xss_clean|required');
		
		$this->form_validation->set_rules('trans_billing_name', 'billing Name', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_billing_address', 'billing Address', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_billing_phone', 'billing Phone', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_billing_province', 'billing Province', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_billing_city', 'billing City', 'trim|xss_clean|required');  
		$this->form_validation->set_rules('trans_billing_zipcode', 'billing Zipcode', 'trim|xss_clean|required');
		
		$this->form_validation->set_rules('trans_id', '', 'trim|xss_clean|required');
		
		if($this->form_validation->run() == FALSE) {
			$this->sci->assign('trans' , $trans);
			$this->sci->da('edit_address.htm');
		} else {
			$this->db->set('trans_shipping_name' , $this->input->post('trans_shipping_name') );
			$this->db->set('trans_shipping_address' , $this->input->post('trans_shipping_address') );
			$this->db->set('trans_shipping_phone' , $this->input->post('trans_shipping_phone') );
			$this->db->set('trans_shipping_province' , $this->input->post('trans_shipping_province') );
			$this->db->set('trans_shipping_city' , $this->input->post('trans_shipping_city') );
			$this->db->set('trans_shipping_zipcode' , $this->input->post('trans_shipping_zipcode') );
			
			$this->db->set('trans_billing_name' , $this->input->post('trans_billing_name') );
			$this->db->set('trans_billing_address' , $this->input->post('trans_billing_address') );
			$this->db->set('trans_billing_phone' , $this->input->post('trans_billing_phone') );
			$this->db->set('trans_billing_province' , $this->input->post('trans_billing_province') );
			$this->db->set('trans_billing_city' , $this->input->post('trans_billing_city') );
			$this->db->set('trans_billing_zipcode' , $this->input->post('trans_billing_zipcode') );
			
			$this->db->where('trans_id' , $this->input->post('trans_id'));
			$this->db->update('transaction');
			
			$this->session->set_confirm(1);
			redirect($this->mod_url."view/$trans_id");
		}
	}
	
	
	function cancel_transaction($trans_id=0) {
		$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();
		$this->sci->assign('trans' , $trans);
		 
		$this->db->join('product_quantity pq' , 'pq.pq_id = transaction_detail.pq_id' , 'left');
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans['trans_id']);
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();
		
		$this->sci->assign('trans_detail' , $trans_detail);
		$this->load->library('form_validation');
		$this->form_validation->set_rules('tch_reason', 'Reason', 'trim|xss_clean');    
		
		if($this->form_validation->run() == FALSE) { 
			$this->sci->da('cancel_transaction.htm');
		} else {
			$this->load->model('mod_stock');
			
			$this->db->set('trans_id' , $this->input->post('trans_id'));
			$this->db->set('tch_last_status' , $trans['trans_payment_status']);
			$this->db->set('tch_reason' , $this->input->post('tch_reason') );
			$this->db->set('u_id' , $this->userinfo['u_id'] );
			$this->db->set('tch_entry' , 'NOW()', FALSE);
			$this->db->insert('transaction_cancel_history');
			
			foreach($trans_detail as $k=>$tmp) {
				$config = array();
				$config['id'] = $tmp['pq_id'];
				$config['change'] = $tmp['transd_quantity'];
				$config['trans_id'] = $tmp['trans_id'];
				$config['note'] = "Cancellation Trans ID: $trans_id";
				$config['action'] = "stock_in";
				$config['u_id'] = $this->userinfo['u_id'];
				$this->mod_stock->stock_in($config);
			}
			
			
			
			$this->db->where('trans_id' , $this->input->post('trans_id'));
			$this->db->set('trans_payment_status' , "Cancelled");
			$this->db->update('transaction');
			
			$this->session->set_confirm(1);
			//redirect($this->mod_url."view/$trans_id");
			redirect($this->session->get_bread('list'));
		}
	}





}
