<?php

class Address extends MY_Controller {

	var $mod_title = 'Address Book';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('myaccount');
		$this->_init();
		$this->session->validate_member();

		$this->sci->assign('mod_title' , $this->mod_title); 

		$this->load->model('mod_area_city');
		$this->load->model('mod_area_province');
		$this->load->model('mod_area');

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

	function index() {
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "address book";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->join_setting();
		$this->db->where('madr_status' , 'Active');
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$this->db->order_by('madr_entry' , 'DESC');
		$res = $this->db->get('member_address');
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);

		$this->_load_topbar();
		$this->_load_sidebar();
		$this->sci->da('index.htm');
	}

	function join_setting(){
		$this->db->join('area_province ap' , 'ap.ap_id = member_address.ap_id' , 'left');
		$this->db->join('area_city ac' , 'ac.ac_id = member_address.ac_id' , 'left');
	}

	function validation_setting() {
		$this->form_validation->set_rules('name', 'name', 'required|trim|xss_clean');
		$this->form_validation->set_rules('address', 'address', 'required|trim|xss_clean');
		$this->form_validation->set_rules('province', 'province', 'required|trim|xss_clean');
		$this->form_validation->set_rules('city', 'city', 'required|trim|xss_clean');
		$this->form_validation->set_rules('zipcode', 'zipcode', 'trim|numeric|exact_length[5]|xss_clean');
		$this->form_validation->set_rules('phone', 'phone', 'trim|numeric|xss_clean');
	}

	function database_setting() {
		$this->db->set('m_id' , $this->userinfo['m_id']);
		$this->db->set('madr_name' , $this->input->post('name'));
		$this->db->set('madr_address' , $this->input->post('address'));
		$this->db->set('madr_phone' , $this->input->post('phone'));
		$this->db->set('madr_zipcode' , $this->input->post('zipcode'));
		$this->db->set('ap_id' , $this->input->post('province'));
		$this->db->set('ac_id' , $this->input->post('city'));
	}

	function pre_add_edit() {
		$area_province = $this->mod_area->get_all_province();
		$this->sci->assign('area_province' , $area_province);
	}

	function add() {
		$this->pre_add_edit();
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".$this->mod_url."' >address book</a>";
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
			redirect($this->mod_url);
		}

	}

	function edit($madr_id=0) {
		$this->pre_add_edit();
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "my account";
		$breadcrumb[] = "<a href='".$this->mod_url."' >address book</a>";
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

			//get all city by data's province
			$area_city = $this->mod_area->get_all_city_by_province($data['ap_id']);
			$this->sci->assign('area_city' , $area_city);

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
			redirect($this->mod_url);
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
		redirect($this->mod_url);
	}

	function ajax_get_city_selection($ap_id=0){
		$city = $this->mod_area->get_all_city_by_province($ap_id);
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

	function ajax_add() {
		$this->pre_add_edit();
		$this->load->library('form_validation');
		$this->sci->d('ajax_add.htm');
	}

	function ajax_submit_add() {
		$this->load->library('form_validation');
		$this->validation_setting();
		if($this->form_validation->run() == FALSE) {
			$ret['status'] = 'error';
			$ret['msg'] = validation_errors(' ',' ');
		} else {
			$this->database_setting();
			$this->db->set('madr_entry' , date('Y-m-d H:i:s'));
			$ok = $this->db->insert('member_address');
			if($ok == FALSE) {
				$ret['status'] = 'error';
				$ret['msg'] = 'cannot add address, please try again';
			} else {
				$ret['status'] = 'ok';
				$madr_name = $this->input->post('name');
				$ret['madr_name'] = $madr_name;
				$ret['madr_id'] = $this->db->insert_id();
			}
		}
		echo json_encode($ret);
	}




}
