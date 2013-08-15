<?php
class Area_province extends MY_Controller {

	var $mod_title = 'Manage Province';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'area_province';
	var $id_field = 'ap_id';
	var $status_field = 'ap_status';
	var $entry_field = 'ap_entry';
	var $stamap_field = 'ap_stamp';
	var $deletion_field = 'ap_deletion';
	var $order_field = 'ap_entry';

	var $author_field = 'ap_author';
	var $editor_field = 'ap_editor';

	var $search_in = array('ap_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}
	
	function pre_index() {
		$this->session->validate(array('SHIPPING_MANAGE_AREA'), 'admin');
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
		$this->form_validation->set_rules('ap_name', 'Title', 'trim|xss_clean');
	}

	function database_setter() {
		$this->db->set('ap_name' ,  $this->input->post('ap_name'));
	}


	function pre_add_edit() {
		$this->config->set_item('global_xss_filtering', FALSE);
	}

	function pre_add() { 
	}

	function pre_edit($id=0) {
	}



}
