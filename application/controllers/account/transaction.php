<?php

class Transaction extends MY_Controller {

	//var $mod_title = 'Order';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->session->validate_member();

		$this->sci->assign('mod_title' , $this->mod_title);

		$this->load->model('mod_area');
		$area = $this->mod_area->get_all();
		$this->sci->assign('area' , $area);

		$this->userinfo = $this->session->get_userinfo('member');
	}

	function _load_topbar(){
		$html = $this->sci->fetch('account/topbar.htm');
		$this->sci->assign('account_topbar' , $html);
	}

	function _load_sidebar(){
		$html = $this->sci->fetch('account/sidebar.htm');
		$this->sci->assign('account_sidebar' , $html);
	}

	//function index() {
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = "my account";
	//	$breadcrumb[] = "address book";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->join_setting();
	//	$this->db->where('madr_status' , 'Active');
	//	$this->db->where('m_id' , $this->userinfo['m_id']);
	//	$this->db->order_by('madr_entry' , 'DESC');
	//	$res = $this->db->get('member_address');
	//	$maindata = $res->result_array();
	//	$this->sci->assign('maindata' , $maindata);
	//
	//	$this->_load_topbar();
	//	$this->_load_sidebar();
	//	$this->sci->da('index.htm');
	//}
	function index(){
		redirect(site_url().'account/transaction/view_list');
	}

	function confirmation($pagelimit=15, $offset=0, $orderby='trans_entry', $ascdesc='DESC', $encodedkey='') {
		$this->_load_topbar();
		$this->_load_sidebar();
		$page = 'confirmation/';
		$this->_get_list($page, 'Active', 'Unconfirmed', $pagelimit, $offset, $orderby, $ascdesc, $encodedkey);
		$this->sci->da('confirmation.htm');
	}

	function view_list($pagelimit=15, $offset=0, $orderby='trans_entry', $ascdesc='DESC', $encodedkey='') {
		$this->_load_topbar();
		$this->_load_sidebar();
		$page = 'confirmation/';
		$this->_get_list($page, 'Active', 'any', $pagelimit, $offset, $orderby, $ascdesc, $encodedkey);
		$this->sci->da('view_list.htm');
	}

	function _get_list($page, $status='Active', $paymentstatus='any', $pagelimit=0, $offset=0, $orderby='trans_entry', $ascdesc='DESC', $encodedkey='') {
		$this->session->set_bread('transaction-list');
		$m_id = $this->userinfo['m_id'];

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "transaction";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			//if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from('transaction');
			$this->join_setting();
			$this->db->where('m_id' , $m_id);
			if($paymentstatus != 'any') { $this->db->where('trans_payment_status' , $paymentstatus); }
			$this->db->where('trans_status' , $status);
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('transaction');
		$this->load->library('pagination');
		$config['base_url'] = site_url()."account/transaction/$page". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('transaction');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

	}


	function join_setting(){
		//$this->db->join('area_province ap' , 'ap.ap_id = member_address.ap_id' , 'left');
		//$this->db->join('area_city ac' , 'ac.ac_id = member_address.ac_id' , 'left');
	}

	function validation_setting() {
		$this->form_validation->set_rules('name', 'name', 'required|trim|xss_clean');
		$this->form_validation->set_rules('address', 'address', 'required|trim|xss_clean');
		$this->form_validation->set_rules('province', 'province', 'required|trim|xss_clean');
		$this->form_validation->set_rules('city', 'city', 'required|trim|xss_clean');
		$this->form_validation->set_rules('zipcode', 'zipcode', 'trim|xss_clean');
		$this->form_validation->set_rules('phone', 'phone', 'trim|xss_clean');
	}

	function database_setting() {
		//$this->db->set('m_id' , $this->userinfo['m_id']);
		//$this->db->set('madr_name' , $this->input->post('name'));
		//$this->db->set('madr_address' , $this->input->post('address'));
		//$this->db->set('madr_phone' , $this->input->post('phone'));
		//$this->db->set('madr_zipcode' , $this->input->post('zipcode'));
		//$this->db->set('ap_id' , $this->input->post('province'));
		//$this->db->set('ac_id' , $this->input->post('city'));
	}

	function view_detail($trans_id=0){
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".$this->session->get_bread('transaction-list')."' >transaction</a>";
		$breadcrumb[] = "view detail";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();

		//get detail
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();

		$this->sci->assign('trans' , $trans);
		$this->sci->assign('trans_detail' , $trans_detail);

		$this->_load_topbar();
		$this->_load_sidebar();
		$this->sci->da('view_detail.htm');
	}


	function confirmation_form($trans_id = 0) {
		//check if already confirmed, then show error
		$this->db->where('trans_id' , $trans_id);
		$this->db->where('transc_status' , 'Active');
		$res = $this->db->get('transaction_confirmation');
		if( $res->row_array() ) { show_error('transaction already paid!'); }

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".site_url()."account/transaction/view_list/' >Order History</a>";
		$breadcrumb[] = "confirm payment";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->_load_topbar();
		$this->_load_sidebar();

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
			$bank_account = $bank_account['ba_name'].', acc '.$bank_account['ba_account_no'].' a/n '.$bank_account['ba_account_holder'];

			$this->db->trans_start();

				$this->db->set('trans_id' , $trans_id);
				$this->db->set('pm_id' , $this->input->post('payment_method') );
				$this->db->set('ba_id' , $this->input->post('bank_account') );
				$this->db->set('transc_bank_account' , $bank_account);
				$this->db->set('transc_payment_method' , $payment_method);
				$this->db->set('transc_date' , $confirmation_date );
				$this->db->set('transc_paid_amount' , $this->input->post('paid_amount') );
				$this->db->set('transc_payment_due' , $transaction['trans_payout'] );
				$this->db->set('m_id' , $this->userinfo['m_id'] );
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
			redirect(site_url().'account/transaction/view_list');
		}

	}


















	function add() {
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".site_url()."account/address' >address book</a>";
		$breadcrumb[] = "add new address";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->_load_topbar();
		$this->_load_sidebar();

		$this->load->library('form_validation');
		$this->validation_setting();
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('edit.htm');
		} else {
			$this->database_setting();
			$this->db->set('madr_entry' , date('Y-m-d H:i:s'));
			$ok = $this->db->insert('member_address');
			if($ok == FALSE) {
				$this->session->set_confirm(0, 'error, cannot insert address');
			} else {
				$this->session->set_confirm(1, 'address inserted');
			}
			redirect(site_url()."account/address/");
		}

	}

	function edit($madr_id=0) {
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".site_url()."account/address' >address book</a>";
		$breadcrumb[] = "edit address";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->_load_topbar();
		$this->_load_sidebar();

		$this->load->library('form_validation');
		$this->validation_setting();
		if($this->form_validation->run() == FALSE) {
			$this->join_setting();
			$this->db->where('madr_id' , $madr_id);
			$res = $this->db->get('member_address');
			$data = $res->row_array();
			$this->sci->assign('data' , $data);
			$this->sci->da('edit.htm');
		} else {
			$this->database_setting();
			$this->db->where('madr_id' , $madr_id);
			$ok = $this->db->update('member_address');
			if($ok == FALSE) {
				$this->session->set_confirm(0, 'error, cannot insert address');
			} else {
				$this->session->set_confirm(1, 'address inserted');
			}
			redirect(site_url()."account/address/");
		}

	}

	function delete($madr_id=0) {
		$this->db->where('madr_id' , $madr_id);
		$this->db->set('madr_status' , 'Deleted');
		$ok = $this->db->update('member_address');
		if($ok == FALSE) {
			$this->session->set_confirm(0, 'error, cannot delete address');
		} else {
			$this->session->set_confirm(1, 'address deleted');
		}
		redirect(site_url()."account/address/");
	}

	function ajax_get_city_selection($ap_id){
		$this->db->where('ap_id' , $ap_id);
		$this->db->where('ac_status' , 'Active');
		$res = $this->db->get('area_city');
		$city = $res->result_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_city_selection.htm');
	}

	function ajax_view($madr_id=0) {
		$this->join_setting();
		$this->db->where('madr_id' , $madr_id);
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$res = $this->db->get('member_address');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->sci->d('ajax_view.htm');
	}




}
