<?php
class Product_type extends MY_Controller {

	var $mod_title = 'Product Type';

	var $table_name = 'product_type';
	var $id_field = 'pt_id';
	var $status_field = 'pt_status';
	var $entry_field = 'pt_entry';
	var $stamp_field = 'pt_stamp';
	var $deletion_field = 'pt_deletion';
	var $order_field = 'pt_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pt_name';

	var $author_field = 'pt_author';
	var $editor_field = 'pt_editor';

	var $search_in = array('pt_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('MASTER_DATA_MANAGE'), 'admin');
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
		$this->form_validation->set_rules('pt_name', 'Name', 'trim|required|xss_clean');
	}

	function database_setter() {
		$pt_name = $this->input->post('pt_name');
		$this->db->set('pt_name' , $pt_name ); 
	}


	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}




}
