<?php
class Bank_account extends MY_Controller {

	var $mod_title = 'Manage Bank Account';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'bank_account';
	var $id_field = 'ba_id';
	var $status_field = 'ba_status';
	var $entry_field = 'ba_entry';
	var $stamba_field = 'ba_stamp';
	var $deletion_field = 'ba_deletion';
	var $order_field = 'ba_entry';

	var $author_field = 'ba_author';
	var $editor_field = 'ba_editor';

	var $search_in = array('ba_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('BANK_ACCOUNT_MANAGE'), 'admin');
		$this->userinfo = $this->session->get_userinfo();
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
		$this->form_validation->set_rules('ba_name', 'Cabang', 'trim|required|xss_clean');
		$this->form_validation->set_rules('ba_bank_name', 'Bank Name', 'trim|xss_clean');
		$this->form_validation->set_rules('ba_account_no', 'Account No', 'trim|required|xss_clean');
		$this->form_validation->set_rules('ba_account_holder', 'Account Holder', 'trim|required|xss_clean');
	}

	function database_setter() {
		$this->db->set('ba_bank_name' , $this->input->post('ba_bank_name') );
		$this->db->set('ba_name' , $this->input->post('ba_name') );
		$this->db->set('ba_account_no' , $this->input->post('ba_account_no') );
		$this->db->set('ba_account_holder' , $this->input->post('ba_account_holder') );

		if($_FILES['ba_image']['name'] != '' ) {
			$filename = $this->_upload_image('ba_image');
			$this->db->set('ba_image' , $filename);
		}
	}


	function pre_add_edit() {}

	function pre_add() {}

	function pre_edit($id=0) {} 





}
