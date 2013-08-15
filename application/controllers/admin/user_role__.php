<?php

class User_role extends MY_Controller {

	var $mod_title = 'User Role Management';

	var $table_name = 'user_role';
	var $id_field = 'ur_id';
	var $status_field = 'ur_status';
	var $entry_field = 'ur_entry';
	var $stamp_field = 'ur_stamp';
	var $deletion_field = 'ur_deletion';
	var $order_field = 'ur_entry';
	var $order_dir = 'DESC';
	var $label_field = 'ur_name';
	var $search_in = array('ur_name');

	function __construct() {
		parent::__construct();
		$this->sci->set_room('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}

	function join_setting() { 
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('user.b_id' , $this->branch_id);
		$this->db->or_where('user.b_id' , 0);
	}

	function validation_setting() {
		$this->form_validation->set_rules('ur_name', 'Role', 'trim');
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id );
		$this->db->set('ur_name' , $this->input->post('ur_name'));
	}

	function enum_setting() {

	}

	function pre_add_edit() {
	}

	function pre_add() {

	}

	function pre_edit($id=0) {
	}


	function index() {
		$this->session->set_bread('list');

		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('ur_status' , 'Active');
		$res = $this->db->get('user_role');
		$maindata = $res->result_array();

		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('user_role_key');
		$role_key = $res->result_array();
		$this->sci->assign('role_key' , $role_key);

		foreach($maindata as $k=>$tmp) {
			$maindata[$k]['role_key'] = $role_key;
			//$temp_urk_id = array();
			foreach($role_key as $l=>$tmp2) {
				//$temp_urk_id[] = $tmp2['urk_id'];
				//print_r($maindata[$k]['role_detail']);
				$this->db->where('ur_id' , $tmp['ur_id'] );
				$this->db->where('urk_id' ,  $tmp2['urk_id'] );
				$res = $this->db->get('user_role_detail');
				$result = $res->row_array();
				if($result) {
					$maindata[$k]['role_key'][$l]['detail'] = $result;
				} else {
					$maindata[$k]['role_key'][$l]['detail'] = array();
				}
			}


		}
		//var_dump($maindata);
		$this->sci->assign('maindata' , $maindata);

		$this->sci->da('index.htm');

	}

	function update_key() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('urk_id[][]', 'User Role Key', 'required');
		$this->form_validation->set_rules('ur_id[]', 'User Role', '' );
		if($this->form_validation->run() == FALSE ) {
			$this->index();
		} else {
			$ur_id = $this->input->post('ur_id');
			$urk_id = $this->input->post('urk_id');
			//print_r($urk_id);
			//print_r($ur_id);
			//exit();

			$ok = TRUE;
			foreach($ur_id as $k=>$tmp) {
				$this->db->where('ur_id' , $k);
				$this->db->delete('user_role_detail');
			}

			foreach($urk_id as $k=>$tmp) {
				foreach($tmp as $l=>$tmp2) {
					$this->db->set('ur_id' , $k);
					$this->db->set('urk_id' , $tmp2);
					if( !$this->db->insert('user_role_detail') ) {
						$ok = FALSE;
					}
				}
			}

			$this->session->set_confirm($ok);
			redirect($this->mod_url."index");

		}
	}



/*
	function view_list($pagelimit=15, $offset=0, $orderby='u_entry', $ascdesc='DESC', $encodedkey='') {
		$this->session->set_bread('list');

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		$this->db->start_cache();
		$this->db->join('user_role' , 'user_role.ur_id = user.ur_id' , 'left');
		$this->db->where('u_status' , 'Active');
		$this->db->order_by($orderby , $ascdesc);
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			$this->db->like('u_login', $searchkey);
			$this->db->or_like('u_name', $searchkey);
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->stop_cache();

		$total = $this->db->count_all_results('user');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url.'view_list/'. $pagelimit .'/';
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);
		$res = $this->db->get('user');
		$maindata = $res->result_array();
		$this->db->flush_cache();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('list.htm');
	}

    function ajax_view_list($pagelimit=1, $offset=0, $orderby='u_entry', $ascdesc='DESC', $encodedkey='') {
		$this->session->set_bread('list');

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		$this->db->start_cache();
		$this->db->where('u_status' , 'Active');
		$this->db->order_by($orderby , $ascdesc);
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			$this->db->like('u_login', $searchkey);
			$this->db->or_like('u_name', $searchkey);
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->stop_cache();

		$total = $this->db->count_all_results('user');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url.'ajax_view_list/'. $pagelimit .'/';
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		//$config['target_div'] = '#list_box';
		$this->pagination->initialize($config);
		$res = $this->db->get('user');
		$maindata = $res->result_array();
		$this->db->flush_cache();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
        $this->sci->assign('paging_js', $this->pagination->create_js() );

		$this->sci->d('list.htm');
	}

	function add() {
		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('u_pass', 'Password', 'trim|matches[u_pass_repeat]|required|xss_clean');
		$this->form_validation->set_rules('u_pass_repeat', 'Password', 'trim|required|xss_clean');
		$this->form_validation->set_rules('u_login', 'Username', 'trim|required|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			//get roles
			$res = $this->db->get('user_role');
			$all_role = $res->result_array();
			$this->sci->assign('all_role' , $all_role);

			$this->sci->da('add.htm');
		}else{
			$this->_set_db();
			$this->db->set('u_login' , $this->input->post('u_login'));
			$salt = $this->config->item('salt');
			if($this->input->post('u_pass')) {
				$this->db->set('u_pass' , md5($salt.$this->input->post('u_pass')) );
			}

			if( !$this->db->insert('user') ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url.'view_list');
		}
	}

	function edit($u_id=0) {
		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('u_pass', 'Password', 'trim|matches[u_pass_repeat]|xss_clean');
		$this->form_validation->set_rules('u_pass_repeat', 'Password', 'trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			//get roles
			$res = $this->db->get('user_role');
			$all_role = $res->result_array();
			$this->sci->assign('all_role' , $all_role);
			//get user
			$this->db->join('user_role' , 'user_role.ur_id = user.ur_id' , 'left');
			$this->db->where('u_id' , $u_id);
			$res = $this->db->get('user');
			$user = $res->row_array();

			$this->sci->assign('user' , $user);
			$this->sci->da('edit.htm');
		}else{
			$this->_set_db();

			$salt = $this->config->item('salt');
			if($this->input->post('u_pass')) {
				$this->db->set('u_pass' , md5($salt.$this->input->post('u_pass')) );
			}

			//if( !$this->mod_user->update($u_id, $data) ) {
			$this->db->where('u_id' , $u_id);
			if( !$this->db->update('user') ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url.'view_list');
		}
	}

	function delete($c_id=0) {
		$this->change_status($c_id, 'Deleted');
	}

	function change_status($u_id=0, $u_status="Active") {
		$this->db->where('u_id' , $u_id);
		$res = $this->db->get('user');
		$result = $res->row_array();
		if(!$result) { redirect($this->session->get_bread('list') ); }

		$this->db->set('u_status' , $u_status);
		$this->db->where('u_id' , $u_id);
		$this->session->set_confirm( $this->db->update('user') );
		switch($u_status) {
			case 'Active'	: $action = 'Restore'; break;
			case 'Deleted'	: $action = 'Delete'; break;
			default			: break;
		}
		//$this->mod_content->_log($c_id, $action );
		redirect($this->session->get_bread('list') );
	}

	function _set_rules() {
		$this->form_validation->set_rules('ur_id', 'Role', 'trim');
		$this->form_validation->set_rules('u_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('u_email', 'Email', 'trim|required|valid_email|xss_clean');
		$this->form_validation->set_rules('u_pass_old', 'Old Password', 'trim|xss_clean');
	}

	function _set_db() {
		$this->db->set('u_name' , $this->input->post('u_name'));
		$this->db->set('u_email' , $this->input->post('u_email'));
		$this->db->set('ur_id' , $this->input->post('ur_id'));
	}

*/



}
?>
