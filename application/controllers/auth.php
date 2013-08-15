<?php

class Auth extends MY_Controller {

	var $mod_path;
	var $mod_url;

	var $mod_title = 'Login';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->load->model('mod_brand');
		$this->mod_path = 'auth/';
		$this->mod_url = site_url() . '';
		$this->salt = $this->config->item('salt');
	}

	function validate_member($encoded_lastpage='') {
		$decoded_lastpage = safe_base64_decode($encoded_lastpage);
		$this->session->set_lastpage($decoded_lastpage); 
		$this->session->validate_member(TRUE, $encoded_lastpage);
	}

	function landing($last_page='') {
		$this->session->set_flashdata('show_auth_landing', TRUE);
		redirect(site_url()."home/landing");
	}

	function make_landing($brand_view_mode = 'list'){
		$this->sci->assign('brand_view_mode' , $brand_view_mode); 
		$this->sci->assign('dont_show_login' , 'No');
 
		$brand_onsale = $this->mod_brand->get_promo_onsale(0);
		$this->sci->assign('brand_onsale' , $brand_onsale); 
 
		$brand_comingsoon = $this->mod_brand->get_promo_comingsoon(0);
		$this->sci->assign('brand_comingsoon' , $brand_comingsoon);

		//get main banner
		$mainbanner = array();
		$today = date('Y-m-d H:i:s');
		
		$this->db->where('bn_status' , 'Active'); 
		$this->db->order_by('bn_order' , 'ASC');
		$res = $this->db->get('banner');
		$tmpbanner = $res->result_array();
		$i=0;
		foreach($tmpbanner as $k=>$tmp) {
			$type = $tmp['bn_type'];
			if( $type == 'Static') { 
				$mainbanner[$i]['data'] = $tmp; 
				$mainbanner[$i]['order'] = $tmp['bn_order'];
				$i++;
			} elseif( $type == 'Timed' ) {
				$start_date = $tmp['bn_start_date'];
				$end_date = $tmp['bn_end_date']; 
				if( ($today > $start_date) && ($today < $end_date)) { 
					$mainbanner[$i]['data'] = $tmp; 
					$mainbanner[$i]['order'] = $tmp['bn_order'];
					$i++;
				}
			} 
		}  
		
		$this->sci->assign('mainbanner' , $mainbanner);

		//get featured product
		$featured_product = $this->mod_product->get_featured_product();
		$this->sci->assign('featured_product' , $featured_product);
	}

	function index() {
		redirect(site_url().'admin');
	}
	
	function login2() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules("username", "Username", "required|trim|xss_clean" );
		$this->form_validation->set_rules("pass", "Password", "required|trim|xss_clean" );

		if ($this->form_validation->run() == FALSE) { 
			$this->sci->assign('error_string', $error_string);
			$this->sci->d('login2.htm');
		}
		else {
			$username = $this->input->post('username');
			$password = $this->input->post('pass');

			$login_ok = $this->_do_login($username, $password);

			if ( $login_ok == 'error' || !$login_ok ) {
				$error_string = '<p class="red">Wrong username or password !</p>';
				$this->sci->assign('error_string', $error_string);
				$this->sci->da('login.htm');
				return FALSE;
			}
			elseif($login_ok == 'not_active') {
				$error_string = '<p class="red">You have not activate your account</p>';
				$this->sci->assign('error_string', $login_ok);
				$this->sci->da('login.htm');
				return FALSE;
			}
			elseif( $login_ok == 'ok') {
				
				$redirect_target = ($lastpage!='') ? $lastpage : site_url();
				redirect( $lastpage );
				
				//$memberinfo = $this->session->get_userinfo('member');	
				//if( $memberinfo['m_first_login'] == 'No' ) {
				//	$this->db->where('m_id' , $memberinfo['m_id']);
				//	$this->db->set('m_first_login' , 'Yes');
				//	$this->db->update('member');
				//	
				//	redirect(site_url()."invite/form");
				//} else {
				//	$redirect_target = ($lastpage!='') ? $lastpage : site_url();
				//	redirect( $lastpage );	
				//}
			}

		}
	}

	function login( $encoded_lastpage='' ) {
		
		$this->make_landing();
		$error_string = '';

		if($encoded_lastpage == '') {
			$lastpage = $this->session->get_lastpage();
		} else {
			$lastpage = safe_base64_decode($encoded_lastpage);
		} 

		$this->load->library('form_validation');
		$this->form_validation->set_rules("username", "Username", "required|trim|xss_clean" );
		$this->form_validation->set_rules("pass", "Password", "required|trim|xss_clean" );

		if ($this->form_validation->run() == FALSE) {
			//$error_string = validation_errors();
			$this->sci->assign('error_string', $error_string);
			$this->sci->da('login.htm');
		}
		else {
			$username = $this->input->post('username');
			$password = $this->input->post('pass');

			$login_ok = $this->_do_login($username, $password);

			if ( $login_ok == 'error' || !$login_ok ) {
				$error_string = '<p class="red">Wrong username or password !</p>';
				$this->sci->assign('error_string', $error_string);
				$this->sci->da('login.htm');
				return FALSE;
			}
			elseif($login_ok == 'not_active') {
				$error_string = '<p class="red">You have not activate your account</p>';
				$this->sci->assign('error_string', $login_ok);
				$this->sci->da('login.htm');
				return FALSE;
			}
			elseif( $login_ok == 'ok') {
				
				$redirect_target = ($lastpage!='') ? $lastpage : site_url();
				redirect( $lastpage );
				
				//$memberinfo = $this->session->get_userinfo('member');	
				//if( $memberinfo['m_first_login'] == 'No' ) {
				//	$this->db->where('m_id' , $memberinfo['m_id']);
				//	$this->db->set('m_first_login' , 'Yes');
				//	$this->db->update('member');
				//	
				//	redirect(site_url()."invite/form");
				//} else {
				//	$redirect_target = ($lastpage!='') ? $lastpage : site_url();
				//	redirect( $lastpage );	
				//}
			}

		}
	}

	function _check_active($username) {
		$this->db->where( 'm_status', 'Active');
		$this->db->where( 'm_login' , $username);
		$res = $this->db->get( 'member');
		$result = $res->row_array();
		if(!$result) { return FALSE; }
		if($result['m_is_active'] == 'No') { return FALSE; } else { return TRUE; }
	}

	function _do_login($username , $password) {
		//get the submitted username from database
		$this->db->where( 'm_status', 'Active');
		$this->db->where( 'm_login' , $username);
		$res = $this->db->get( 'member');
		$result = $res->row_array();

		if(!$result) { //if no user is found
			return FALSE;
		} else {

			// check authentication of password submitted
			//if(  $password == $result['m_pass']  ) {
			if(  md5($this->salt.$password) == $result['m_pass']  ) {


				if($result['m_is_active'] == 'No') { return 'not_active'; }

				//IF AUTHENTICATION OK !!!
				$this->session->set_userdata('m_id' , $result['m_id']);

				//update last login
				$this->db->set('m_last_login' , 'NOW()' , FALSE);
				$this->db->where('m_id' , $result[ 'm_id' ] );
				$this->db->update('member');

				//TODO : dont forget to turn on useraction !
				//$this->CI->useraction->login_ok($row->u_id , $loginname);
				return 'ok';
			} else {
				//$this->CI->useraction->login_ng($loginname);
				return 'error';
			}
		}
	}

	function logout() {
		$this->session->unset_userdata('m_id');
		session_destroy();
		redirect( $this->mod_url );
	}

	function no_access() {
		$this->sci->da( 'no_access.htm');
	}

	function admin() {
		$this->session->validate(array('OPERATOR'));
		$this->sci->da( 'admin.htm');
	}

	function forgot_password() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('email', 'Username / Email', 'trim|required|callback_email_check');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('forgot_password.htm');
		} else {
			$email = $this->input->post('email');
			$this->db->where('m_login' , $email);
			$this->db->where('m_status' , 'Active');
			$res = $this->db->get('member');
			$member = $res->row_array();

			$this->send_password_change_email($member['m_id']);

			$this->sci->da('forgot_password_sent.htm');

		}
	}

	public function email_check($str) {
		$this->load->model('mod_member');
		if ($this->mod_member->check_email_registered($str) ) {
			return TRUE;
		} else {
			$this->form_validation->set_message('email_check', "$str is not a valid username / email");
			return FALSE;
		}
	}

	function send_password_change_email($m_id) {
		$this->db->where('m_id' , $m_id);
		$res = $this->db->get('member');
		$member = $res->row_array();

		//send email
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($member['m_login']);

		$this->email->subject( 'Password Change Requested' );
		$this->sci->assign('fullname' , $member['m_firstname'].' '.$member['m_lastname']);
		$this->sci->assign('key' , $member['m_registration_key']);
		$this->sci->assign('m_login_encoded' , safe_base64_encode($member['m_login']));

		$messagebody = $this->sci->fetch('auth/email_password_change.htm');
		$this->email->message($messagebody);
		$ok = $this->email->send();
		//echo $this->email->print_debugger();
		return $ok;
	}

	function change_password($email_encoded='', $registration_key='') {
		$email = safe_base64_decode($email_encoded);
		$this->db->where('m_registration_key' , $registration_key);
		$this->db->where('m_login' , $email);
		$this->db->where('m_status' , 'Active');
		$res = $this->db->get('member');
		$member = $res->row_array();

		if(!$member) {
			show_error('404');
		}

		$this->load->library('form_validation');
		$this->form_validation->set_rules('password', 'Password', 'required|trim|xss_clean');
		$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'required|trim|xss_clean|matches[password]');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('change_password.htm');
		} else {
			$salt = $this->config->item('salt');
			$password = $this->input->post('password');
			$password = md5($salt.$password);
			$this->db->set('m_pass' , $password);
			$this->db->where('m_id' , $member['m_id']);
			$this->db->update('member');

			$this->sci->da('change_password_success.htm');

		}
	}
	
	function ajax_send_activation() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('username', 'Username', 'required|trim|valid_email|xss_clean');
		//$this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'required|trim|xss_clean|matches[password]');
		if($this->form_validation->run() == FALSE) {
			//$this->sci->d('send_activation_form.htm');
			$return['status'] = 'error';
			$return['msg'] = validation_errors(); 
		} else {
			
			$this->db->where('m_login' , $this->input->post('username'));
			$res = $this->db->get('member');
			$member = $res->row_array();
	
			//send email
			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
			$this->email->to($member['m_login']);
	
			$this->email->subject( 'Activate Your Account');
			$this->sci->assign('fullname' , $member['m_firstname'].' '.$member['m_lastname']);
			$this->sci->assign('key' , $member['m_activation_key']);
			$this->sci->assign('member' , $member);
	
			$messagebody = $this->sci->fetch('auth/email_activation.htm');
			$this->email->message($messagebody);
			$ok = $this->email->send(); 
			if($ok) {
				$this->load->model('mod_member');
				$this->mod_member->activation_email_sent($member['m_id']);
				$return['status'] = 'ok';
				$return['msg'] = '<div class="green">Activation email has been sent to your email</div>'; 
			} else {
				$return['status'] = 'error';
				$return['msg'] = '<div class="red">Cannot send email right now, please try again later</div>'; 
				//$this->session->set_confirm(0, $this->email->print_debugger());
			}
			
		}
		echo json_encode($return);
	}

}
