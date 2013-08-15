<?php
class Package_category extends MY_Controller {

	var $mod_title = 'Package Category';

	var $table_name = 'package_category';
	var $id_field = 'pkc_id';
	var $status_field = 'pkc_status';
	var $entry_field = 'pkc_entry';
	var $stamp_field = 'pkc_stamp';
	var $deletion_field = 'pkc_deletion';
	var $order_field = 'pkc_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pkc_name';

	var $search_in = array('pkc_name');

	var $menu_group = 'setup';

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

		$this->sci->assign('menu_group' , $this->menu_group);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		//$this->db->join('content_label as parent' , 'parent.pkc_id = content_label.pkc_parent_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('b_id' , $this->branch_id);
	}

	function validation_setting() {
		$this->form_validation->set_rules('pkc_name', 'Name', 'trim|required|xss_clean');
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id );
		$pkc_name = $this->input->post('pkc_name');
		$this->db->set('pkc_name' , $pkc_name );
	}



	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}



}
