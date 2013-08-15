<?php

class UserAction  {

	function UserAction() {
		$this->CI =& get_instance();
	}
	
	function addHistory($u_id = 0 , $ua_id = 0 , $param1 = '' , $param2 = '' , $param3 = '' , $success = 'False') {
		$this->CI->db->set('u_id' , $u_id);
		$this->CI->db->set('ua_id' , $ua_id);
		$this->CI->db->set('uah_param_1' , $param1);
		$this->CI->db->set('uah_param_2' , $param2);
		$this->CI->db->set('uah_param_3' , $param3);
		$this->CI->db->set('uah_success' , $success);
		$this->CI->db->set('uah_time' , 'NOW()' , FALSE);
		$this->CI->db->set('uah_ip' , $this->CI->input->ip_address());
		$this->CI->db->insert('user_action_history');
	}
	
	function add_session_history($ua_id = 0 , $param1 = '' , $param2 = '' , $param3 = '' , $success = 'False') {
		$user_info = $this->CI->session->get_user_information();
		$user_id = $user_info['u_id'];
		$this->addHistory($user_id , $ua_id , $param1 , $param2 , $param3 , $success);
	}
	
	function login_ok($userid = 0 , $loginname = '') {
		$this->addHistory($userid , 1 , $loginname , 'ADMIN' , '' , 'True');
	}
	
	function login_ng($loginname = '') {
		$this->addHistory(0 , 1 , $loginname , 'ADMIN' , '' , 'False');
	}
	
	function logout($u_id = 0) {
		$this->addHistory($u_id , 2 , '' , '' , '' , 'True');
	}
	
	function admin_create($page = '' , $idname = '' , $value = '') {
		$this->add_session_history(23 , $page , $idname , $value , 'True');
	}

	function admin_edit($page = '' , $idname = '' , $value = '') {
		$this->add_session_history(24 , $page , $idname , $value , 'True');
	}

	function admin_delete($page = '' , $idname = '' , $value = '') {
		$this->add_session_history(25 , $page , $idname , $value , 'True');
	}

	function void_transaction($is_id = '') {
		$this->add_session_history(26 , $is_id , '' , '' , 'True');
	}
	
}
?>
