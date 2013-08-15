<?php
class Product_subcategory extends MY_Controller {

	var $mod_title = 'Product Sub Category';

	var $table_name = 'product_subcategory';
	var $id_field = 'psc_id';
	var $status_field = 'psc_status';
	var $entry_field = 'psc_entry';
	var $stamp_field = 'psc_stamp';
	var $deletion_field = 'psc_deletion';
	var $order_field = 'psc_entry';
	var $order_dir = 'DESC';
	var $label_field = 'psc_name';

	var $author_field = 'psc_author';
	var $editor_field = 'psc_editor';

	var $search_in = array('psc_name');


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
		$this->db->join('product_category pc' , 'pc.pc_id = product_subcategory.pc_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('psc_name', 'Name', 'trim|required|xss_clean');
		//$this->form_validation->set_rules('c_id', 'Link To Content', 'trim|xss_clean');
	}

	function database_setter() { 
		$this->db->set('psc_name' , $this->input->post('psc_name')  );
		$this->db->set('pc_id' , $this->input->post('pc_id') );
	}


	function pre_add_edit() {

		$this->db->where('pc_status' , 'Active');
		$this->db->order_by('pc_name' , 'asc');
		$res = $this->db->get('product_category');
		$product_category = $res->result_array();
		$this->sci->assign('product_category' , $product_category);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}




}
