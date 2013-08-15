<?php

class User extends MY_Controller {

	var $mod_title = 'User Management';

	var $table_name = 'user';
	var $id_field = 'u_id';
	var $status_field = 'u_status';
	var $entry_field = 'u_entry';
	var $stamp_field = 'u_stamp';
	var $deletion_field = 'u_deletion';
	var $order_field = 'u_entry';
	var $order_dir = 'DESC';
	var $label_field = 'u_name';
	var $search_in = array('u_name', 'u_email');

	var $template_edit  = 'edit.htm';
	var $template_add  = 'edit.htm';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('USER_MANAGE'), 'admin');
		//$this->sci->assign('use_ajax' , TRUE);
		$this->userinfo = $this->session->get_userinfo();
	}


	function join_setting() {
		$this->db->join('user_role' , 'user_role.ur_id = user.ur_id' , 'left');
	}

	function where_setting() {
		//$this->db->where('user.b_id' , $this->branch_id);
		//$this->db->where('user.u_super !=' , 'yes');
		//$this->db->or_where('user.b_id' , 0);
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting($action='add') {
		$this->form_validation->set_rules('ur_id', 'Role', 'trim');
		$this->form_validation->set_rules('u_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('u_login', 'Login Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('u_email', 'Email', 'trim|required|valid_email|xss_clean');
		if($action == 'add') {
			$this->form_validation->set_rules('new_pass', 'Password', 'trim|matches[u_pass_repeat]|required|xss_clean');
			$this->form_validation->set_rules('u_pass_repeat', 'Password', 'trim|required|xss_clean');
		} else {
			$this->form_validation->set_rules('u_pass_old', 'Old Password', 'trim|xss_clean');
			$this->form_validation->set_rules('new_pass', 'Password', 'trim|matches[u_pass_repeat]|xss_clean');
			$this->form_validation->set_rules('u_pass_repeat', 'Password', 'trim|xss_clean');
		}

	}

	function database_setter($action='add') {
		if($action == 'add') {
			$this->db->set('b_id' , $this->branch_id );
		}
		$this->db->set('u_name' , $this->input->post('u_name'));
		$this->db->set('u_login' , $this->input->post('u_login'));
		$this->db->set('u_email' , $this->input->post('u_email'));
		$this->db->set('ur_id' , $this->input->post('ur_id'));

		$salt = $this->config->item('salt');
		if($action == 'add') {
			if($this->input->post('new_pass')) {
				$this->db->set('u_pass' , md5($salt.$this->input->post('new_pass')) );
			} else {
				//$this->db->set('u_pass' , $this->input->post('u_pass_old') );
			}
		} else {
			if($this->input->post('new_pass')) {
				$this->db->set('u_pass' , md5($salt.$this->input->post('new_pass')) );
			}
		}


	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function pre_add_edit() {
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('ur_status' , 'Active');
		$res = $this->db->get('user_role');
		$all_role = $res->result_array();
		$this->sci->assign('all_role' , $all_role);
	}

	function pre_add() {

	}

	function pre_edit($id=0) {
		$this->db->where('u_id' , $id);
		$res = $this->db->get('user');
		$user = $res->row_array();
		$this->sci->assign('user' , $user);
	}

	function myself() {
		$this->session->set_bread('list');

		$this->db->join('user_role' , 'user_role.ur_id = user.ur_id' , 'left');
		$this->db->where('user.u_id' , $this->userinfo['u_id']);
		$res = $this->db->get('user');
		$user = $res->row_array();
		$this->sci->assign('user' , $user);

		$this->load->library('form_validation');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('myself.htm');
		}
	}

	function myself_edit($id=0) {
		$this->sci->assign('ajax_action' , 'edit');
		$this->pre_add_edit();
		$this->join_setting();
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->pre_edit($id);

		$this->load->library('form_validation');
		$this->validation_setting('edit');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da("myself_edit.htm");
		}else{
			$this->database_setter('edit');
			$this->db->where($this->id_field , $id);
			$ok = $this->db->update($this->table_name);
			$this->post_edit($id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url."edit/$id");
			} else {
				$this->session->set_confirm(1);
				redirect($this->session->get_bread('list') );
			}
		}
	}



}
?>
