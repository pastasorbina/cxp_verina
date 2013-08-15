<?php

class Profile extends MY_Controller {

	var $mod_title = 'MY ACCOUNT';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('account');
		$this->_init();


		$this->sci->assign('mod_title' , $this->mod_title);
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
		$this->session->validate_member();
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "My Account";
		$breadcrumb[] = "Profile";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->_load_topbar();
		$this->_load_sidebar();

		$this->db->where('m_id' , $this->userinfo['m_id'] );
		$res = $this->db->get('member');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->sci->da('index.htm');
	}

	function edit() {
		$this->session->validate_member();
		$this->load->library('form_validation');
		$this->form_validation->set_rules('m_firstname', 'Firstname', 'trim|required|xss_clean');
		$this->form_validation->set_rules('m_lastname', 'Lastname', 'trim|required|xss_clean');
		$this->form_validation->set_rules('m_sex', 'Gender', 'trim|required|xss_clean');

		if($this->form_validation->run() == FALSE) {
			$breadcrumb = array();
			$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
			$breadcrumb[] = "My Account";
			$breadcrumb[] = "<a href='".site_url()."account/profile' >Profile</a>";
			$breadcrumb[] = "Edit";
			$this->sci->assign('breadcrumb' , $breadcrumb);
			$this->_load_topbar();
			$this->_load_sidebar();

			$this->db->where('m_id' , $this->userinfo['m_id'] );
			$res = $this->db->get('member');
			$data = $res->row_array();
			$this->sci->assign('data' , $data);

			$this->sci->da('edit.htm');
		} else {
			$this->db->where('m_id' , $this->userinfo['m_id'] );
			$this->db->set('m_firstname' , $this->input->post('m_firstname'));
			$this->db->set('m_lastname' , $this->input->post('m_lastname'));
			$this->db->set('m_sex' , $this->input->post('m_sex'));
			$ok = $this->db->update('member');

			if($ok) {
				$this->session->set_confirm(1, 'Profile Updated');
			} else {
				$this->session->set_confirm(0, 'Cannot Update Profile');
			}
			redirect($this->mod_url);
		}
	}

	function change_password() {
		$this->session->validate_member();
		$this->db->where('m_id' , $this->userinfo['m_id'] );
		$res = $this->db->get('member');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		if($data['m_pass'] != '') {
			$this->form_validation->set_rules('old_password', 'Old Password', 'trim|required|xss_clean|callback__check_old_password');
		}
		$this->form_validation->set_rules('password', 'New Password', 'trim|required|xss_clean');
		$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|xss_clean|matches[password]');

		if($this->form_validation->run() == FALSE) {
			$breadcrumb = array();
			$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
			$breadcrumb[] = "My Account";
			$breadcrumb[] = "<a href='".site_url()."account/profile' >Profile</a>";
			$breadcrumb[] = "Change Password";
			$this->sci->assign('breadcrumb' , $breadcrumb);
			$this->_load_topbar();
			$this->_load_sidebar();
			$this->sci->da('change_password.htm');
		} else {
			$salt = $this->config->item('salt');
			$password = md5($salt.$this->input->post('password'));
			$this->db->where('m_id' , $this->userinfo['m_id'] );
			$this->db->set('m_pass' , $password);
			$ok = $this->db->update('member');

			if($ok) {
				$this->session->set_confirm(1, 'Password Changed');
			} else {
				$this->session->set_confirm(0, 'Cannot Change Password');
			}
			redirect($this->mod_url);
		}
	}


	function resetp($m_registration_key='') {
		$member = get_member_by_registration_key($m_registration_key);
		if(!$member) { show_error('User Not Found!'); return FALSE; }
		$this->sci->assign('data' , $member);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('password', 'New Password', 'trim|required|xss_clean');
		$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|xss_clean|matches[password]');

		if($this->form_validation->run() == FALSE) {
			$breadcrumb = array();
			$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
			$this->sci->assign('breadcrumb' , $breadcrumb);
			$this->_load_topbar();

			$this->sci->da('resetp.htm');
		} else {
			$salt = $this->config->item('salt');
			$password = md5($salt.$this->input->post('password'));
			$this->db->where('m_id' , $member['m_id'] );
			$this->db->set('m_pass' , $password);
			$ok = $this->db->update('member');

			if($ok) {
				$this->session->set_confirm(1, 'Password Changed');
			} else {
				$this->session->set_confirm(0, 'Cannot Change Password');
			}
			redirect(site_url());
		}
	}


	function _check_old_password($str) {
		$this->db->where('m_id' , $this->userinfo['m_id'] );
		$res = $this->db->get('member');
		$data = $res->row_array();
		$salt = $this->config->item('salt');
		$password = md5($salt.$str);
		if (!$data || $data['m_pass'] != $password) {
			$this->form_validation->set_message('_check_old_password', 'Old password doesn\'t match !');
			return FALSE;
		} else {
			return TRUE;
		}
	}

}
