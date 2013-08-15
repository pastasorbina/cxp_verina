<?php
class Polling_option extends MY_Controller {

	var $mod_title = 'Polling Option';

	var $table_name = 'polling_option';
	var $id_field = 'pollo_id';
	var $status_field = 'pollo_status';
	var $entry_field = 'pollo_entry';
	var $stamp_field = 'pollo_stamp';
	var $deletion_field = 'pollo_deletion';
	var $order_field = 'pollo_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pollo_name';

	var $author_field = 'pollo_author';
	var $editor_field = 'pollo_editor';

	var $search_in = array('pollo_name');

	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('MASTER_DATA_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);


	}

	function iteration_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('polling_category pollc' , 'pollc.pollc_id = polling_option.pollc_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");

	}

	function validation_setting() {
		$this->form_validation->set_rules('pollo_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pollc_id', 'Category', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pollo_order', 'Order', 'trim|xss_clean');
	}

	function database_setter() {
		$pollo_name = $this->input->post('pollo_name');
		$this->db->set('pollo_name' , $pollo_name );
		$this->db->set('pollc_id' , $this->input->post('pollc_id') );
		$this->db->set('pollo_order' , $this->input->post('pollo_order') );
	}


	function pre_add_edit() {
		$polling_category = $this->mod_global->get_options('polling_category' , 'pollc_id' , 'pollc_name' , "pollc_status = 'Active'" , 'pollc_id');
		$this->sci->assign('polling_category' , $polling_category);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}




}
