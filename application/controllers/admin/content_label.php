<?php
class Content_label extends MY_Controller {

	var $mod_title = 'Content Label';

	var $table_name = 'content_label';
	var $id_field = 'cl_id';
	var $status_field = 'cl_status';
	var $entry_field = 'cl_entry';
	var $stamp_field = 'cl_stamp';
	var $deletion_field = 'cl_deletion';
	var $order_field = 'cl_entry';
	var $order_dir = 'DESC';
	var $label_field = 'cl_name';

	var $search_in = array('cl_name');

	var $menu_group = 'setup';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

		$this->sci->assign('menu_group' , $this->menu_group);

	}

	function enum_setting($maindata=array()) {
		foreach($maindata as $k=>$tmp) {
			$this->db->where('cl_id' , $tmp['cl_parent_id'] );
			$this->db->where('cl_status' , 'Active');
			$res = $this->db->get('content_label');
			$parent = $res->row_array();
			$maindata[$k]['parent'] = $parent;
		}
		return $maindata;
	}

	function join_setting() {
		//$this->db->join('content_label as parent' , 'parent.cl_id = content_label.cl_parent_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('b_id' , $this->branch_id);
	}

	function validation_setting() {
		$this->form_validation->set_rules('cl_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('cl_parent_id', 'Parent ID', 'trim|numeric|xss_clean');
		$this->form_validation->set_rules('cl_type', 'Type', 'trim|xss_clean');
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id );
		$cl_name = $this->input->post('cl_name');
		$this->db->set('cl_name' , $cl_name );
		$this->db->set('cl_code' , str_replace(' ','_', strtolower(trim($cl_name)) ) );
		$this->db->set('cl_parent_id' , $this->input->post('cl_parent_id') );
		$this->db->set('cl_type' , $this->input->post('cl_type') );
	}



	function pre_add_edit() {
	}

	function pre_add() {
		$this->db->where('cl_status' , 'Active');
		$res = $this->db->get('content_label');
		$all_parent = $res->result_array();
		$this->sci->assign('all_parent' , $all_parent);
	}

	function pre_edit($id=0) {
		$this->db->where('cl_status' , 'Active');
		$this->db->where('cl_id !=' , $id);
		$res = $this->db->get('content_label');
		$all_parent = $res->result_array();
		$this->sci->assign('all_parent' , $all_parent);
	}



}
