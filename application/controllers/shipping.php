<?php

class Shipping extends MY_Controller {


	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->session->validate_member();

		$this->sci->assign('mod_title' , $this->mod_title);

		$this->load->model('mod_gift_card');
		$this->load->model('mod_member');

		$this->userinfo = $this->session->get_userinfo('member');
	}


	function index(){

	}

	function join_setting(){
		$this->db->join('member' , 'member.m_id = gift_card.gc_sender_id' , 'left');
	}
	function validation_setting() { }
	function database_setting() { }


	//function view_list($gc_is_used="No", $pagelimit=15, $offset=0) {
	//
	//	$this->_load_topbar();
	//	$this->_load_sidebar();
	//	$this->session->set_bread('giftcard-list');
	//	$m_id = $this->userinfo['m_id'];
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = "my account";
	//	$breadcrumb[] = "Gift Card List";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->assign('gc_is_used' , $gc_is_used);
	//	$this->sci->assign('pagelimit' , $pagelimit);
	//	$this->sci->assign('offset' , $offset);
	//	/*--cache-start--*/
	//	$this->db->start_cache();
	//		if($gc_is_used != 'any'){
	//			$this->db->where('gc_is_used' , $gc_is_used);
	//			$this->db->order_by('gc_used_date' , 'ASC');
	//			//$this->db->order_by('gc_entry' , 'DESC');
	//		} elseif($gc_is_used != 'Yes') {
	//			$this->db->order_by('gc_used_date' , 'DESC');
	//		} elseif($gc_is_used != 'No') {
	//			$this->db->order_by('gc_entry' , 'DESC');
	//		}
	//		$this->join_setting();
	//		$this->db->where('gift_card.m_id' , $m_id);
	//		$this->db->where('gc_status' , 'Active');
	//	$this->db->stop_cache();
	//	/*--cache-stop--*/
	//
	//	// Pagination
	//	$total = $this->db->count_all_results('gift_card');
	//	$this->load->library('pagination');
	//	$config['base_url'] = site_url()."myaccount/gift_card/view_list". $gc_is_used."/". $pagelimit ."/";
	//	$config['suffix'] = "/" ;
	//	$config['total_rows'] = $total;
	//	$config['per_page'] = $pagelimit;
	//	$config['uri_segment'] = 6;
	//	$this->pagination->initialize($config);
	//
	//	$this->db->limit($pagelimit, $offset);
	//	$res = $this->db->get('gift_card');
	//	$this->db->flush_cache();
	//	$maindata = $res->result_array();
	//	$this->sci->assign('maindata' , $maindata);
	//	$this->sci->assign('paging', $this->pagination->create_links() );
	//
	//	$this->sci->da('view_list.htm');
	//}
	//
	//function view_select_list($pagelimit=15, $offset=0) {
	//	$this->_load_topbar();
	//	$this->_load_sidebar();
	//	$m_id = $this->userinfo['m_id'];
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = "my account";
	//	$breadcrumb[] = "voucher list";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->assign('pagelimit' , $pagelimit);
	//	$this->sci->assign('offset' , $offset);
	//	/*--cache-start--*/
	//	$this->db->start_cache();
	//		$this->db->order_by('gc_entry' , 'DESC');
	//		$this->join_setting();
	//		$this->db->where('gift_card.m_id' , $m_id);
	//		$this->db->where('gc_status' , 'New');
	//	$this->db->stop_cache();
	//	/*--cache-stop--*/
	//
	//	// Pagination
	//	$total = $this->db->count_all_results('voucher');
	//	$this->load->library('pagination');
	//	$config['base_url'] = site_url()."myaccount/gift_card/view_select_list". $pagelimit ."/";
	//	$config['suffix'] = "/" ;
	//	$config['total_rows'] = $total;
	//	$config['per_page'] = $pagelimit;
	//	$config['uri_segment'] = 5;
	//	$this->pagination->initialize($config);
	//
	//	$this->db->limit($pagelimit, $offset);
	//	$res = $this->db->get('gift_card');
	//	$this->db->flush_cache();
	//	$maindata = $res->result_array();
	//	$this->sci->assign('maindata' , $maindata);
	//	$this->sci->assign('paging', $this->pagination->create_links() );
	//
	//	$this->sci->d('view_select_list.htm');
	//}

	function ajax_select_shipping_price($sm_id=0) {
		$this->sci->assign('sm_id' , $sm_id);

		$this->db->where('sm_status' , 'Active');
		$res = $this->db->get('shipping_method');
		$all_shipping_method = $res->result_array();
		$this->sci->assign('all_shipping_method' , $all_shipping_method);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('confirm', '', 'trim|xss_clean');
		$this->form_validation->set_rules('searchkey', '', 'trim|xss_clean');
		if($this->form_validation->run() == false) {
			$this->sci->d('ajax_select_shipping_price.htm');
		} else {

			$this->db->trans_start();
			//tambah credit
			$this->mod_member->topup_saldo( $this->userinfo['m_id'], $gift_card['gc_nominal'] );
			//disable gift card
			$this->mod_gift_card->disable($gc_id);
			$this->db->trans_complete();

			$this->session->set_confirm(1);
			//redirect( $this->session->get_bread('giftcard-list'));
		}
	}

	function ajax_search_by_province($sm_id='') {
		$searchkey = $this->input->post('searchkey');
		$sm_id = $this->input->post('sm_id');
		$this->sci->assign('searchkey' , $searchkey);

		$this->db->where('sp_status' , 'Active');
		$this->db->where('sm_id' , $sm_id);
		$this->db->like('sp_city' , $searchkey);
		$res = $this->db->get('shipping_price');
		//print $searchkey;
		$shipping_price = $res->result_array();
		//print_r($shipping_price);
		$this->sci->assign('shipping_price' , $shipping_price);
		$this->sci->d('city_list.htm');
	}




	function redeem_voucher() {
		$gc_code = $this->input->post('voucher_code');
		$this->db->where('gc_code' , $gc_code);
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$this->db->where('gc_status' , 'New');
		$res = $this->db->get('voucher');
		$voucher = $res->row_array();

		//check validity of the code
		if(!$voucher) {
			$ret['status'] = 'error';
			$ret['msg'] = 'wrong voucher code, or voucher has been used';
			$ret['data'] = array();
		} else {
			$ret['status'] = 'ok';
			$ret['msg'] = 'ok';
			$ret['nominal'] = number_format($voucher['gc_nominal'],2);
			$ret['code'] = $voucher['gc_code'];
		}
		echo json_encode($ret);
	}

	function share($gc_id=0) {
		$gift_card = $this->mod_gift_card->get_gift_card($gc_id);
		//if giftcard is invalid, throw back
		if(!$gift_card) { redirect( $this->session->get_bread('giftcard-list')); }
		$this->sci->assign('gift_card' , $gift_card);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('receiver_email', 'Email', 'trim|required|valid_email|xss_clean|callback_email_check');
		if($this->form_validation->run() == false) {
			$this->_load_topbar();
			$this->_load_sidebar();
			$this->sci->da('share.htm');
		} else {

			//$this->db->trans_start();
			////tambah credit
			//$this->mod_member->topup_saldo( $this->userinfo['m_id'], $gift_card['gc_nominal'] );
			////disable gift card
			//$this->mod_gift_card->disable($gc_id);
			//$this->db->trans_complete();

			//$this->session->set_confirm(1);
			//redirect( $this->session->get_bread('giftcard-list'));
			$encoded_email = safe_base64_encode($this->input->post('receiver_email'));

			redirect( site_url(). "myaccount/gift_card/confirm_share/$gc_id/$encoded_email");
		}
	}

	function confirm_share($gc_id=0, $encoded_email=''){
		$gift_card = $this->mod_gift_card->get_gift_card($gc_id);
		//if giftcard is invalid, throw back
		if(!$gift_card) { redirect( $this->session->get_bread('giftcard-list')); }
		$this->sci->assign('gift_card' , $gift_card);

		$email = safe_base64_decode($encoded_email);
		$this->sci->assign('email' , $email);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('confirm', '', 'trim|required|xss_clean');
		if($this->form_validation->run() == false) {
			$this->_load_topbar();
			$this->_load_sidebar();
			$this->sci->da('confirm_share.htm');
		} else {

			//get member by email
			$receiver = $this->mod_member->get_by_email($email);

			//if member is not available, redirect back
			if(!$receiver) { redirect( $this->session->get_bread('giftcard-list')); }

			$ok = $this->mod_gift_card->transfer($gc_id, $this->userinfo['m_id'], $receiver['m_id'] );
			$this->session->set_confirm(1);
			redirect( $this->session->get_bread('giftcard-list'));
		}
	}

	//callback if email already registered
	public function email_check($str) {
		$this->load->model('mod_member');
		if ($this->mod_member->check_email_registered($str) ) {
			return TRUE;
		} else {
			$this->form_validation->set_message('email_check', $str.' is not a member');
			return FALSE;
		}
	}






	function ajax_redeem() {
		$this->load->library('form_validation');
		$this->sci->d('ajax_redeem.htm');
	}

	function do_redeem_by_code() {
		$this->load->library('form_validation');
		$gc_code = $this->input->post('giftcard_code');

		//search giftcard with that code and m_id
		$m_id = $this->userinfo['m_id'];
		$this->db->where('m_id' , $m_id);
		$this->db->where('gc_code' , $gc_code);
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array();

		if($gift_card) {
			//do credit topup
			$ok = $this->mod_member->topup_saldo($m_id, $gift_card['gc_nominal']);
			$new_credit = $this->mod_member->get_saldo($m_id);
			//disable giftcard
			$ok = $this->mod_gift_card->disable($gift_card['gc_id']);

			$ret['ok'] = $ok;
			$ret['status'] = 'ok';
			$ret['gc_code'] = $gc_code;
			$ret['gc_nominal'] = number_format($gift_card['gc_nominal'], 0);
			$ret['current_credit'] = number_format($new_credit, 0);
		} else {
			$ret['status'] = 'not_found';
			$ret['msg'] = 'wrong giftcard code / code not valid !';
		}

		echo json_encode($ret);
	}

	function do_check_code() {
		$this->load->library('form_validation');
		$gc_code = $this->input->post('check_giftcard_code');

		//search giftcard with that code and m_id
		$m_id = $this->userinfo['m_id'];
		$this->db->where('m_id' , $m_id);
		$this->db->where('gc_code' , $gc_code);
		$this->db->where('gc_is_used' , 'No');
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array();

		if($gift_card) {
			$ret['status'] = 'ok';
			$ret['gc_code'] = $gc_code;
			$ret['gc_nominal'] = number_format($gift_card['gc_nominal'], 0);
			$ret['msg'] = 'gift card found';
		} else {
			$ret['status'] = 'not_found';
			$ret['msg'] = 'wrong giftcard code / code not valid !';
		}

		echo json_encode($ret);
	}




}
