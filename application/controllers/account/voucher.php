<?php

class Voucher extends MY_Controller {

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
		redirect(site_url().'account/voucher/view_list');
	}


	function view_list($pagelimit=15, $offset=0) {
		$this->_load_topbar();
		$this->_load_sidebar();
		$this->session->set_bread('voucher-list');
		$m_id = $this->userinfo['m_id'];

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "voucher list";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		/*--cache-start--*/
		$this->db->start_cache();
			$this->db->order_by('v_entry' , 'DESC');
			$this->join_setting();
			$this->db->where('m_id' , $m_id);
			$this->db->where('v_status' , 'New');
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('voucher');
		$this->load->library('pagination');
		$config['base_url'] = site_url()."account/voucher/view_list". $pagelimit ."/";
		$config['suffix'] = "/" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('voucher');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		$this->sci->da('view_list.htm');
	}

	function view_select_list($pagelimit=15, $offset=0) {
		$this->_load_topbar();
		$this->_load_sidebar();
		$m_id = $this->userinfo['m_id'];

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "voucher list";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		/*--cache-start--*/
		$this->db->start_cache();
			$this->db->order_by('v_entry' , 'DESC');
			$this->join_setting();
			$this->db->where('m_id' , $m_id);
			$this->db->where('v_status' , 'New');
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('voucher');
		$this->load->library('pagination');
		$config['base_url'] = site_url()."account/voucher/view_select_list". $pagelimit ."/";
		$config['suffix'] = "/" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('voucher');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		$this->sci->d('view_select_list.htm');
	}

	function redeem_voucher() {
		$v_code = $this->input->post('voucher_code');
		$this->db->where('v_code' , $v_code);
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$this->db->where('v_status' , 'New');
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
			$ret['nominal'] = number_format($voucher['v_nominal'],2);
			$ret['code'] = $voucher['v_code'];
		}
		echo json_encode($ret);
	}




	function join_setting(){
	}

	function validation_setting() {
	}

	function database_setting() {
	}




}
