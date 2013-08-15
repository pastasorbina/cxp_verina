<?php

class MY_Session extends CI_Session {

	var $CI;
	var $prefix = 'C3gunsou';
	var $area = '';

	var $salt = '';
	var $user_set = array();

	public $room;
	public $userinfo;

	function __construct()
	{
		parent::__construct();
		$this->CI =& get_instance();

		//get user set from configuration file
		$this->user_set = $this->CI->config->item('user_set');

		//get salt from configuration
		$this->salt = $this->CI->config->item('salt');

	}

	/** init()
	 **/
	function init( $head = "admin" ) {
	}

	function do_login($username , $password) {

		//firsts of all, set user table configuration to be used for authentication
		$room = $this->CI->sci->get_room();
		$fields = array();
		$fields = $this->user_set[$room];

		//TODO: differentiate between different room's cookie cache

		//get the submitted username from database
		$this->CI->db->where( $fields['user']['status_field'] , 'Active');
		$this->CI->db->where( $fields['user']['username_field'] , $username);
		$res = $this->CI->db->get( $fields['user']['table_name'] );
		$result = $res->row_array();

		if(!$result) { //if no user is found
			return FALSE;
		} else {
			//if($result['b_id'] != BRANCH_ID && $result['u_super'] != 'yes') { return FALSE; }
			//TODO: should check user registered between branch ?

			// check authentication of password submitted
			if(  md5($this->salt . $password) == $result[ $fields['user']['password_field'] ]  ) {

				//IF AUTHENTICATION OK !!!
				$this->set_userdata($room.'u_id' , $result[ $fields['user']['id_field'] ] );


				//check and assign roles
				// TODO: should check roles ?

				//get role keys and assign to session
				//$this->CI->db->where('user.u_id' , $result[ $fields['user']['id_field'] ]  );
				//$this->CI->db->join('user' , 'user.ur_id = user_role.ur_id' , 'left');
				//$res = $this->CI->db->get( 'user_role' );
				//$roles = $res->result_array();
				//$_SESSION['roles'] = $roles;

				//update last login
				//$this->CI->db->set('u_last_login' , 'NOW()' , FALSE);
				//$this->CI->db->where('u_id' , $result[ $fields['user']['id_field'] ] );
				//$this->CI->db->update('user');

				//TODO : dont forget to turn on useraction !
				// Login sucessful, update useraction
				//$this->CI->useraction->login_ok($row->u_id , $loginname);
				return TRUE;
			} else {
				//$this->CI->useraction->login_ng($loginname);
				return FALSE;
			}
		}
	}


	function do_logout() {
		// Do logout here
		//$this->CI->useraction->logout($this->CI->session->userdata('user_id'));
		$room =  $this->CI->sci->get_room();

		// Destroy CI session !
		//$this->sess_destroy();
		//only unset userdata for the current room
		$this->unset_userdata($room.'u_id');
		//and Destroy PHP Session !
		session_destroy();
	}

	//access current userinfo @public
	public function get_userinfo($room='admin') {
		$status = FALSE;
		switch($room){
			case 'admin'	:
				$sess_uid = $this->userdata('u_id');
				$this->u_id = $sess_uid;
				$this->userinfo = $this->_get_user_information('admin');
				$this->CI->sci->assign('userinfo', $this->userinfo);
				$this->CI->sci->assign('LOGGEDIN', '1');
				$this->CI->userinfo = $this->userinfo;
				$status = TRUE;
				break;
			case 'member'	:
				$sess_uid = $this->userdata('m_id');
				$this->m_id = $sess_uid;
				$this->userinfo = $this->_get_user_information('member');
				$this->CI->sci->assign('userinfo', $this->userinfo);
				$this->CI->sci->assign('LOGGEDIN', '1');
				$this->CI->userinfo = $this->userinfo;
				$status = TRUE;
				break;
		}
		return $this->userinfo;
	}




	public function validate($roles = array(), $room = 'admin', $redirect = TRUE) {
		$status = 0;

		$fields = $this->user_set[$room];

		// Check if user is logged in
		$session_u_id = $this->userdata($room.'u_id');

		if ($session_u_id != FALSE) {
			// Get user information
			$this->u_id = $session_u_id;
			//get user information and assign it to page
			$this->userinfo = $this->_get_user_information($room);
			$this->CI->sci->assign('userinfo', $this->userinfo);
			$this->CI->sci->assign('LOGGEDIN', '1');

			$status = 1;
		}
		else {
			//get currently viewed page before redirected to login page
			$lastpage = safe_base64_encode(current_url());
			//$lastpage = '';

			$room = $this->CI->sci->get_room();
			//print $room;

			//if redirect is set TRUE, then redirect the page
			if($redirect == TRUE) {
				if($room){
					redirect(site_url() . $room. "/auth/login/".$lastpage);
				} elseif($room == 'admin' ) {
					redirect(site_url() . "admin/auth/login/".$lastpage);
				}else {
					redirect(site_url() . "auth/login/".$lastpage);
				}
			}

			//or just throw status 2
			$status = 2;
		}

		$flag = TRUE;

		//TODO: validate user role authentication

		//get role keys to compare with logged in user
		$this->CI->db->where('user_role.ur_id' , $this->userinfo['ur_id']  );
		//$this->CI->db->join('user' , 'user.ur_id = user_role.ur_id' , 'left');
		$this->CI->db->join('user_role' , 'user_role.ur_id = user_role_detail.ur_id' , 'left');
		$this->CI->db->join('user_role_key' , 'user_role_key.urk_id = user_role_detail.urk_id' , 'left');
		$this->CI->db->select('user_role_key.urk_key');
		$res = $this->CI->db->get( 'user_role_detail' );
		$role_keys_temp = $res->result_array();

		$role_keys = array();
		foreach($role_keys_temp as $k=>$tmp) {
			$role_keys[$k] = $tmp['urk_key'];
		}
		//print_r($role_keys);
		//print_r($registered_roles);
		foreach($roles as $val) {
			if (!in_array($val , $role_keys)) {
				// This user doesn't have an appropriate access level
				if($redirect == TRUE) {
					if($room){
						//redirect(site_url(). $room ."/auth/no_access/");
					} elseif($room == 'admin' ) {
						//redirect(site_url(). "admin/auth/no_access/");
					}else {
						//redirect(site_url(). "auth/no_access/");
					}
				}
				$flag = FALSE;
			}
		}

		if($flag == FALSE AND !@$this->roles) {
			$status = 3;
		}
		return $status;
	}


	/* get_user_information
	 *
	 *	@access privare
	 */
	private function _get_user_information($room='admin') {
		if($room == 'admin') {
			$user_set = $this->CI->config->item('user_set');
			$fields = array();
			$fields = $user_set[$room];
			$u_id = $this->userdata($room.'u_id');

			// Return user information if asked from controller
			if ($u_id != FALSE) {
				$id_field =  $fields['user']['id_field'];
				$this->CI->db->where($id_field , $u_id);
				$res = $this->CI->db->get($fields['user']['table_name']);
				$result = $res->row_array();
				return $result;
			} else { return FALSE; }

		} elseif($room='member') {
			$m_id = $this->userdata('m_id');
			if ($m_id != FALSE) {
				$this->CI->db->where('m_id' , $m_id);
				$res = $this->CI->db->get('member');
				$result = $res->row_array();
				return $result;
			} else { return FALSE; }
		}
	}

	/* validate member */
	public function validate_member($redirect = TRUE, $encoded_lastpage='') {
		$status = 0;

		// Check if user is logged in
		$sess_m_id = $this->userdata('m_id');
		//print $sess_m_id;

		if ($sess_m_id) {
			$this->m_id = $sess_m_id;
			$this->userinfo = $this->_get_user_information('member');
			$this->CI->sci->assign('userinfo', $this->userinfo);
			$this->CI->sci->assign('LOGGEDIN', '1');
			$this->CI->userinfo = $this->userinfo;
			$status = 1;
		} else{
			$this->CI->load->library('uri');
			$lp = $this->CI->uri->segment(1);
			if($encoded_lastpage == '') {
				$encoded_lastpage = safe_base64_encode(current_url());
			}
			if($lp = 'home') {
				if($redirect == TRUE) { redirect(site_url() . "auth/login/".$encoded_lastpage); }
			} else {
				if($redirect == TRUE) { redirect(site_url() . "auth/login/".$encoded_lastpage); }
			}
			$status = 2;
		}

		$flag = TRUE;
		return $status;
	}

	/*
	 *	get user info
	 *	@access public
	 */
	public function get_user_info($room='admin') {
		$status = FALSE;
		switch($room){
			case 'admin'	:
				$sess_uid = $this->userdata('u_id');
				$this->u_id = $sess_ui;
				$this->userinfo = $this->_get_user_information('admin');
				$this->CI->sci->assign('userinfo', $this->userinfo);
				$this->CI->sci->assign('LOGGEDIN', '1');
				$this->CI->userinfo = $this->userinfo;
				$status = TRUE;
				break;
			case 'member'	:
				$sess_uid = $this->userdata('m_id');
				$this->m_id = $sess_uid;
				$this->userinfo = $this->_get_user_information('member');
				$this->CI->sci->assign('userinfo', $this->userinfo);
				$this->CI->sci->assign('LOGGEDIN', '1');
				$this->CI->userinfo = $this->userinfo;
				$status = TRUE;
				break;
		}
		return $status;
	}

	// Check just one role
	function check_role($role) {
		return in_array($role , $this->roles);
	}

	// Check just one tag
	function check_tag($tag) {
		return in_array($tag , $this->tags);
	}

	/**
	* check current area of the site to distinguished different cookie prefix
	* @
	*/
	 function check_current_area() {
		$area = $this->CI->uri->segment(1);
		if (!is_readable(APPPATH."controllers/".$area)) {
			$area = '';
		}
		return $area;
	}

	/**
	* get confirmation type and message to flashdata
	* @
	*/
	function set_confirm($type='', $msg='') {
		$confirm['type'] = $type;
		$confirm['msg'] = $msg;
		$this->set_flashdata($this->prefix.'_confirm', $confirm);
	}

	/**
	* retrieve and assign confirmation message to page
	* @
	*/
	function get_confirm($token = 'CONFIRM_MSG') {
		$string = '';
		if($this->flashdata($this->prefix.'_confirm')) {
			$confirm = $this->flashdata($this->prefix.'_confirm');
			$confirm['token'] = $token;
			$this->assign_confirm($confirm['type'], $confirm['msg'], $token);
			return $confirm;
		}
	}

	/**
	* assign confirmation message
	* @
	*/
	function assign_confirm($type=0, $msg='', $token='CONFIRM_MSG' ) {
		if($type == 1) {
			$class = 'success';
			$msg = $msg?$msg:'Operation Success !';
		} else {
			$class = 'error';
			$msg = $msg?$msg:'Operation Error !';
		}
		//$string = '<div class="alert-message '.$class.' fade in">
		//		<a class="close" href="#"><img src="'.site_url().'img/icons/cancel.png" /></a>
		//		'.$msg.'
		//	</div>';
		$string = '<div class="alert alert-'.$class.' fade in">
				<a class="close" href="#"><span style="font-size:13px;color:#ffffff;" >x</span></a>
				'.$msg.'
			</div>';

		$this->CI->sci->assign($token, $string);
	}

	/**
	* set lastpage
	* @
	*/
	function set_lastpage($lastpage='') {
		if($lastpage == '') {
			$lastpage = current_url();
		}
		//$this->set_userdata('___lastpage', $lastpage );
		$_SESSION['__lastpage'] = $lastpage;
	}

	/**
	* retrieve lastpage
	* @
	*/
	function get_lastpage() {
		//$url = $this->userdata('___lastpage');
		$url = '';
		//$url = $_SESSION['__lastpage'];
		if(isset($_SESSION['__lastpage'])) { $url = $_SESSION['__lastpage']; }
		return $url;
	}

	/**
	* set breadcrumbs
	* @
	*/
	function set_bread($type='list' ) {
		$callback_url = site_url().$this->CI->uri->uri_string();
		$key = $this->prefix.'_bread_'.$type;
		//$this->set_userdata($key, $callback_url);
		$_SESSION[$key] = $callback_url;
	}

	/**
	* retrieve breadcrumbs
	* @
	*/
	function get_bread($type='list') {
		$key = $this->prefix.'_bread_'.$type;
		//$url = $this->userdata($this->prefix.'_bread_'.$type);
		$url = '';
		if(isset($_SESSION[$key])) { $url = $_SESSION[$key]; }
		return $url;
	}



	/**
	 * sess_update
	 *
	 * memastikan session tidak diupdate apabila ini pemanggilan AJAX
	 */
	function sess_update() {
		// skip the session update if this is an AJAX call!
		if ( !IS_AJAX ) {
			parent::sess_update();
		}
	}


}
