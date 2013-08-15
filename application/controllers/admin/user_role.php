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

	var $menu_group = 'user';

	var $template_edit = 'edit.htm';
	var $template_add = 'edit.htm';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('USER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);
	}

	function join_setting() {
		//$this->db->join('user_role' , 'user_role.ur_id = user.ur_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		//$this->db->where('b_id' , $this->branch_id);
	}

	function validation_setting($action) {
		$this->form_validation->set_rules('ur_name', 'Role', 'trim');
		if($action == 'edit') {
			$this->form_validation->set_rules('urk_id[]', 'Rolekey ID', 'trim');
		}
	}

	function database_setter($action='add') {
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
		$this->db->where('ur_id' , $id);
		//$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('user_role');
		$role = $res->row_array();
		$this->sci->assign('role' , $role);

		$this->db->where('ur_id' , $id);
		$res = $this->db->get('user_role_detail');
		$role_detail = $res->result_array();
		$rda = array();
		foreach($role_detail as $k=>$tmp) {
			$rda[] = $tmp['urk_id'];
		}

		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('user_role_key');
		$role_key = $res->result_array();
		foreach($role_key as $k=>$tmp) {
			if(in_array($tmp['urk_id'], $rda)) {
				$checked = 'yes';
			} else {
				$checked = 'no';
			}
			$role_key[$k]['checked'] = $checked;
		}
		$this->sci->assign('role_key' , $role_key);
	}

	function post_edit($id=0) {
		$urk_id_arr = $this->input->post('urk_id');

		$this->db->where('ur_id' , $id);
		$this->db->delete('user_role_detail');

		foreach($urk_id_arr as $k=>$tmp) {
			$this->db->set('ur_id' , $id);
			$this->db->set('urk_id' , $tmp);
			$this->db->insert('user_role_detail');
		}
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
			foreach($role_key as $l=>$tmp2) {
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

		$this->sci->assign('maindata' , $maindata);

		$this->sci->da('index.htm');

	}

	//function pre_index() {
	//	$this->db->where('b_id' , $this->branch_id);
	//	$res = $this->db->get('user_role_key');
	//	$role_key = $res->result_array();
	//	$this->sci->assign('role_key' , $role_key);
	//}
	//
	//function iteration_setting($maindata) {
	//	foreach($maindata as $k=>$tmp) {
	//		$maindata[$k]['role_key'] = $role_key;
	//		foreach($role_key as $l=>$tmp2) {
	//			$this->db->where('ur_id' , $tmp['ur_id'] );
	//			$this->db->where('urk_id' ,  $tmp2['urk_id'] );
	//			$res = $this->db->get('user_role_detail');
	//			$result = $res->row_array();
	//			if($result) {
	//				$maindata[$k]['role_key'][$l]['detail'] = $result;
	//			} else {
	//				$maindata[$k]['role_key'][$l]['detail'] = array();
	//			}
	//		}
	//	}
	//	return $maindata;
	//}


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



}
?>
