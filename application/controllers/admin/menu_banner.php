<?php
class Menu_banner extends MY_Controller {

	var $mod_title = 'Menu Banner';

	var $table_name = 'menu_banner';
	var $id_field = 'mb_id';
	var $status_field = 'mb_status';
	var $entry_field = 'mb_entry';
	var $stamp_field = 'mb_stamp';
	var $deletion_field = 'mb_deletion';
	var $order_field = 'mb_order';
	var $order_dir = 'ASC';
	var $label_field = 'mb_title';

	var $search_in = array('mb_title');

	var $menu_group = 'setup';
	
	var $image_directory = 'userfiles/menu_banner/';
	var $thumb_directory = 'userfiles/menu_banner/thumb/';
	var $thumb_width = 125;
	var $thumb_height = 125;
	var $maintain_ratio = TRUE;


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

		$this->sci->assign('menu_group' , $this->menu_group);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		//$this->db->join('content_label as parent' , 'parent.mb_id = content_label.mb_parent_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('b_id' , $this->branch_id);
		
	}

	function validation_setting() {
		$this->form_validation->set_rules('mb_title', 'Title', 'trim|required|xss_clean');
		$this->form_validation->set_rules('mb_subtext', 'Subtext', 'trim|xss_clean');
		$this->form_validation->set_rules('mb_desc', 'Desc', 'trim|xss_clean');
		$this->form_validation->set_rules('mb_url', 'Url', 'trim');
		$this->form_validation->set_rules('mb_order', 'Order', 'trim|numeric');
		$this->form_validation->set_rules('mb_code', 'Code', 'trim|xss_clean');
	}

	function database_setter() {
		if($_FILES['mb_image_small']['name'] != '') {
			$filename = $this->_upload_image( 'mb_image_small');
			$this->db->set('mb_image_small' , $filename);
		}
		
		if($_FILES['mb_image_large']['name'] != '') {
			$filename = $this->_upload_image( 'mb_image_large');
			$this->db->set('mb_image_large' , $filename);
		}
		$this->db->set('b_id' , $this->branch_id );
		$mb_title = $this->input->post('mb_title');
		$this->db->set('mb_title' , $mb_title );
		
		$this->db->set('mb_desc' , $this->input->post('mb_desc') );
		$this->db->set('mb_url' , $this->input->post('mb_url') );
		$this->db->set('mb_subtext' , $this->input->post('mb_subtext') );
		$this->db->set('mb_order' , $this->input->post('mb_order') );
		$this->db->set('mb_code' , $this->input->post('mb_code') );
	}



	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}



}
