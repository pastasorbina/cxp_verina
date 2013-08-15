<?php
class Shipping_area extends MY_Controller {

	var $mod_title = 'Manage Shipping Area';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'shipping_area';
	var $id_field = 'sa_id';
	var $status_field = 'sa_status';
	var $entry_field = 'sa_entry';
	var $stamsa_field = 'sa_stamp';
	var $deletion_field = 'sa_deletion';
	var $order_field = 'sa_entry';

	var $author_field = 'sa_author';
	var $editor_field = 'sa_editor';

	var $search_in = array('sa_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('SHIPPING_MANAGE_AREA'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
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
		$this->form_validation->set_rules('sa_name', 'Name', 'trim|required|xss_clean');
	}

	function database_setter() {
		$this->db->set('sa_name' , $this->input->post('sa_name') );
	}

	function pre_add_edit() {  }
	function pre_add() { }
	function pre_edit($id=0) { 	}
	function delete($id=0) { $this->change_status($id, 'Deleted'); }


	




}
