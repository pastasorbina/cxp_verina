<?php
class Product_category extends MY_Controller {

	var $mod_title = 'Product Category';

	var $table_name = 'product_category';
	var $id_field = 'pc_id';
	var $status_field = 'pc_status';
	var $entry_field = 'pc_entry';
	var $stamp_field = 'pc_stamp';
	var $deletion_field = 'pc_deletion';
	var $order_field = 'pc_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pc_name';

	var $author_field = 'pc_author';
	var $editor_field = 'pc_editor';

	var $search_in = array('pc_name');


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
		//$this->db->join('content c' , 'c.c_id = product_category.c_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('pc_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pc_parent_id', 'Parent', 'trim|xss_clean');
	}

	function database_setter() {
		$this->db->set('pc_name' , $this->input->post('pc_name') );
		$this->db->set('pc_parent_id' , $this->input->post('pc_parent_id') );
		$this->db->set('pc_slug' , url_title($this->input->post('pc_name'),'-',TRUE) );

		//$this->image_directory = 'userfiles/product_category/';
		//$this->thumb_directory = 'userfiles/product_category/thumb/';
		//$this->thumb_width = 125;
		//$this->thumb_height = 125;
		//
		//if($_FILES['pc_image']['name'] != '') {
		//	$filename = $this->_upload_image('pc_image');
		//	$this->db->set('pc_image' , $filename);
		//}
	}

	function iteration_setting($maindata = array() ) {
		foreach($maindata as $k=>$tmp) {
			$this->db->where('pc_id' , $tmp['pc_parent_id'] );
			$res = $this->db->get('product_category');
			$parent = $res->row_array();
			$maindata[$k]['parent'] = $parent;
		}
		return $maindata;
	}


	function pre_add_edit() {
		//$this->db->where('c_status' , 'Active');
		//$res = $this->db->get('content');
		//$all_content = $res->result_array();
		//$this->sci->assign('all_content' , $all_content);

		$this->db->where('pc_parent_id' , '0');
		$this->db->where('pc_status' , 'Active');
		$this->db->order_by('pc_name' , 'asc');
		$res = $this->db->get('product_category');
		$parent = $res->result_array();
		$this->sci->assign('parent' , $parent);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}




}
