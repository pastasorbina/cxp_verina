<?php

class Voucher extends MY_Controller {

	//var $mod_title = 'Order';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('myaccount');
		$this->_init();
		$this->session->validate_member();

		$this->sci->assign('mod_title' , $this->mod_title);

		$this->load->model('mod_voucher');
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

	function index(){
		redirect(site_url().'myaccount/voucher/view_list');
	}

	function join_setting(){
		$this->db->join('voucher_set vs' , 'vs.vs_id = voucher.vs_id' , 'left');
		$this->db->join('voucher_type vt' , 'vs.vt_id = vt.vt_id' , 'left');
	}


	function view_list($v_status="New", $pagelimit=30, $offset=0) {
		$this->_load_topbar();
		$this->_load_sidebar();
		$this->session->set_bread('voucher-list');
		$m_id = $this->userinfo['m_id'];

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "voucher list";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->assign('v_status' , $v_status);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		/*--cache-start--*/
		$this->db->start_cache();
			$this->db->order_by('v_entry' , 'DESC');
			$this->join_setting();
			$this->db->where('m_id' , $m_id);
			$this->db->where('v_status' , $v_status);
			$this->db->where('v_start_date <=' , date('Y-m-d H:i:s') );
			$this->db->where('v_end_date >' , date('Y-m-d H:i:s') );
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('voucher');
		$this->load->library('pagination');
		$config['base_url'] = site_url()."myaccount/voucher/view_list/$v_status/$pagelimit/";
		$config['suffix'] = "/" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('voucher');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		if($v_status != 'Used') {
			//get promo voucher
			$voucher_promo = $this->mod_voucher->get_all_voucher_promo();
			$this->sci->assign('voucher_promo' , $voucher_promo);
		}

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
			$this->db->where('v_start_date <=' , date('Y-m-d H:i:s') );
			$this->db->where('v_end_date >' , date('Y-m-d H:i:s') );
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('voucher');
		$this->load->library('pagination');
		$config['base_url'] = site_url()."myaccount/voucher/view_select_list". $pagelimit ."/";
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

		//get promo voucher
		$voucher_promo = $this->mod_voucher->get_all_voucher_promo();
		$this->sci->assign('voucher_promo' , $voucher_promo);

		$this->sci->d('view_select_list.htm');
	}

	function redeem_voucher() {
		//type ada dua : normal / promo
		$type = $this->input->post('voucher_type');

		$v_code = $this->input->post('voucher_code');

		$error = TRUE;
		$error_msg = '<div clas="red">wrong voucher code, or voucher has been used</div>';

		//first, check that code through voucher (normal)
		$voucher = $this->mod_voucher->get_valid_voucher($v_code, $this->userinfo['m_id']);

		if($voucher) {
			$error = FALSE;
			$voucher_code = $voucher['v_code'];
			$voucher_nominal = price_format($voucher['v_nominal']);
			$voucher_nominal_raw = strip_zero($voucher['v_nominal']);
			$error_msg = '
						<div clas="green">voucher validated</div>';
		} else {
			//if not exist, search through voucher with type = promo
			$this->db->join('voucher_type' , 'voucher_type.vt_id = voucher_set.vt_id' , 'left');
			$this->db->where('vs_code' , $v_code);
			$this->db->where('vt_code' , 'promo');
			$this->db->where('vs_status' , 'Active');
			$res = $this->db->get('voucher_set');
			$voucher_set = $res->row_array();
			//print_r($voucher_set);
			if(!$voucher_set) {
				$error = TRUE;
			} else {
				$this->db->where('vs_id' , $voucher_set['vs_id']);
				$this->db->where('m_id' , $this->userinfo['m_id']);
				$this->db->where('v_used' , 'Yes');
				$res = $this->db->get('voucher');
				$voucher_in_set = $res->row_array();
				if($voucher_in_set) {
					$error = TRUE;
				} else {
					//check if payout is below the minimum purchase
					$voucher_cart_final_payout = $this->input->post('voucher_cart_final_payout');
					$min_purchase = price_format($voucher_set['vs_min_purchase']);
					if($voucher_cart_final_payout < $voucher_set['vs_min_purchase'] ) {
						$error = TRUE;
						$error_msg = '
						<div clas="red">cannot use voucher, you must purchase a minimum of Rp. '.$min_purchase.',- </div>';
					} else {
						$error = FALSE;
						$voucher_nominal_raw = strip_zero($voucher_set['vs_nominal']);
						$voucher_nominal = price_format($voucher_set['vs_nominal']); 
						$voucher_code = $voucher_set['vs_code'];
						$error_msg = '
						<div clas="green">voucher validated</div>';
					}
				}

			}
		}

		//check validity of the code
		if($error == TRUE) {
			$ret['status'] = 'error';
			$ret['msg'] = $error_msg;
			$ret['data'] = array();
		} else {
			$ret['status'] = 'ok';
			$ret['msg'] = $error_msg;
			$ret['nominal'] = $voucher_nominal;
			$ret['raw'] = $voucher_nominal_raw;
			$ret['code'] = $voucher_code;
		}
		echo json_encode($ret);
	}






	function validation_setting() {
	}

	function database_setting() {
	}




}
