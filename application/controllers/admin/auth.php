<?php

/**
 * Auth Controller
 * (admin)
 *
 */

class Auth extends MY_Controller {

	var $mod_path;
	var $mod_url;

	var $mod_title = 'Login';

	function __construct() { 
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();

		$this->mod_path = 'auth/';
		$this->mod_url = site_url().'admin/';
	}

	function index() {
		redirect(site_url().'admin');
	}

	function login( $lastpage='' ) {
		$error_string = '';
		$lastpage = safe_base64_decode($lastpage);

		$this->load->library('form_validation');
		$this->form_validation->set_rules("username", "Username", "required|trim|xss_clean" );
		$this->form_validation->set_rules("pass", "Password", "required|trim|xss_clean" );

		if ($this->form_validation->run() == FALSE) {
			$error_string = validation_errors();
			$this->sci->assign('error_string', $error_string);
			$this->sci->da('login.htm');
		}
		else {
			$login_ok = $this->session->do_login($this->input->post('username'), $this->input->post('pass'));
			if ( $login_ok == TRUE ) {
				$redirect_target = ($lastpage!='') ? $lastpage : $this->mod_url ;
				redirect( $redirect_target );
			} else {
				$error_string = '<p class="red">Wrong username or password !</p>';
				$this->sci->assign('error_string', $error_string);
				$this->sci->da('login.htm');
			}
		}
	}

	function logout() {
		$this->session->do_logout();
		redirect( $this->mod_url );
	}

	function no_access() {
		$this->mod_title = 'No Access !';
		$this->session->validate(array(), 'admin');
		$this->sci->da( 'no_access.htm');
	}

	function admin() {
		$this->session->validate(array('OPERATOR'));
		$this->sci->da( 'admin.htm');
	}

}
