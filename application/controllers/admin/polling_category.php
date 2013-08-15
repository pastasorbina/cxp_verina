<?php
class Polling_category extends MY_Controller {

	var $mod_title = 'Polling Category';

	var $table_name = 'polling_category';
	var $id_field = 'pollc_id';
	var $status_field = 'pollc_status';
	var $entry_field = 'pollc_entry';
	var $stamp_field = 'pollc_stamp';
	var $deletion_field = 'pollc_deletion';
	var $order_field = 'pollc_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pollc_name';

	var $author_field = 'pollc_author';
	var $editor_field = 'pollc_editor';

	var $search_in = array('pollc_name');

	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('MASTER_DATA_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('pollc_name', 'Name', 'trim|required|xss_clean');
	}

	function database_setter() {
		$pollc_name = $this->input->post('pollc_name');
		$this->db->set('pollc_name' , $pollc_name );
	}


	function pre_add_edit() { 
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}




}
