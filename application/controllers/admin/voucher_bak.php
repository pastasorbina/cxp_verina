<?php
class Voucher extends MY_Controller {

	var $mod_title = 'Voucher';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'voucher';
	var $id_field = 'v_id';
	var $status_field = 'v_status';
	var $entry_field = 'v_entry';
	var $stamv_field = 'v_stamp';
	var $deletion_field = 'v_deletion';
	var $order_field = 'v_entry';

	var $author_field = 'v_author';
	var $editor_field = 'v_editor';

	var $search_in = array('v_code');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->sci->assign('use_ajax' , FALSE);

		//$this->product_type = $this->mod_product_type->get_list('*', array('pt_status'=>'Active'));
		//$this->sci->assign('product_type' , $this->product_type);
		//
		//$this->product_category = $this->mod_product_category->get_list('*', array('pc_status'=>'Active'));
		//$this->sci->assign('product_category' , $this->product_category);
		//
		//$this->brand = $this->mod_brand->get_list('*', array('br_status'=>'Active'));
		//$this->sci->assign('brand' , $this->brand);
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
		$this->form_validation->set_rules('p_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('pc_id', 'Category', 'trim|xss_clean');
		$this->form_validation->set_rules('pt_id', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('br_id', 'Brand', 'trim|xss_clean');
		$this->form_validation->set_rules('p_order', 'Ordering', 'trim|xss_clean');
		$this->form_validation->set_rules('p_price', 'Price', 'trim|xss_clean');
		$this->form_validation->set_rules('p_discount_price', 'Discounted Price', 'trim|xss_clean');
		$this->form_validation->set_rules('p_weight', 'Weight', 'trim|xss_clean');
		$this->form_validation->set_rules('p_description', 'Description', 'trim');
	}

	function database_setter() {
		$this->db->set('pc_id' , $this->input->post('pc_id'));
		$this->db->set('pt_id' , $this->input->post('pt_id'));
		$this->db->set('br_id' , $this->input->post('br_id'));
		$this->db->set('p_order' , $this->input->post('p_order'));
		$this->db->set('p_name' , $this->input->post('p_name') );
		$this->db->set('p_code' , $this->input->post('p_code') );
		$this->db->set('p_price' , $this->input->post('p_price') );
		$this->db->set('p_discount_price' , $this->input->post('p_discount_price') );
		$this->db->set('p_description' , $this->input->post('p_description') );
		$this->db->set('p_weight' , $this->input->post('p_weight') );

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['p_image1']['name'] != '' ) {
			$filename = $this->_upload_image('p_image1');
			$this->db->set('p_image1' , $filename);
		}
		if($_FILES['p_image2']['name'] != '' ) {
			$filename = $this->_upload_image('p_image2');
			$this->db->set('p_image2' , $filename);
		}
		if($_FILES['p_image3']['name'] != '' ) {
			$filename = $this->_upload_image('p_image3');
			$this->db->set('p_image3' , $filename);
		}
		if($_FILES['p_image4']['name'] != '' ) {
			$filename = $this->_upload_image('p_image4');
			$this->db->set('p_image4' , $filename);
		}
		if($_FILES['p_image5']['name'] != '' ) {
			$filename = $this->_upload_image('p_image5');
			$this->db->set('p_image5' , $filename);
		}
	}


	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function delete($id=0) {
		$this->change_status($id, 'Deleted');
	}

    function generate() {
        $this->sci->da('generate.htm');
    }





}
