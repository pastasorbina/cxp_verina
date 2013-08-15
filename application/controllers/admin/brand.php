<?php
class Brand extends MY_Controller {

	var $mod_title = 'Brands';

	var $table_name = 'brand';
	var $id_field = 'br_id';
	var $status_field = 'br_status';
	var $entry_field = 'br_entry';
	var $stamp_field = 'br_stamp';
	var $deletion_field = 'br_deletion';
	var $order_field = 'br_entry';
	var $order_dir = 'DESC';
	var $label_field = 'br_name';

	var $author_field = 'br_author';
	var $editor_field = 'br_editor';

	var $search_in = array('br_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);
		$this->config->set_item('global_xss_filtering', FALSE);
	}
	
	function pre_index() {
		$this->session->validate(array('BRAND_VIEW_LIST'), 'admin');
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
		$this->form_validation->set_rules('br_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('br_delivery_guide', 'Delivery Guide', 'trim');
	}

	function database_setter() {
		$br_name = $this->input->post('br_name');
		$this->db->set('br_name' , $br_name );
		$this->db->set('br_delivery_guide' , $this->input->post('br_delivery_guide') );

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['br_image_header']['name'] != '') {
			$filename = $this->_upload_image('br_image_header');
			$this->db->set('br_image_header' , $filename);
		}
		if($_FILES['br_image_square']['name'] != '') {
			$filename = $this->_upload_image('br_image_square');
			$this->db->set('br_image_square' , $filename);
		}
		if($_FILES['br_image_square_grayscale']['name'] != '') {
			$filename = $this->_upload_image('br_image_square_grayscale');
			$this->db->set('br_image_square_grayscale' , $filename);
		}
		if($_FILES['br_image_rectangle']['name'] != '') {
			$filename = $this->_upload_image('br_image_rectangle');
			$this->db->set('br_image_rectangle' , $filename);
		} 
	}


	function pre_add_edit() {
		$this->session->validate(array('BRAND_EDIT'), 'admin');
	}

	function pre_add() {
	}

	function pre_edit($id=0) { 
	}
	
	
	function ajax_get_data($br_id=0) {
		$this->db->where('br_id' , $br_id);
		$res = $this->db->get('brand');
		$result = $res->row_array();
		echo json_encode($result);
	}
 

}
