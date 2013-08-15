<?php
class Content_image extends MY_Controller {

	var $mod_title = 'Content Image';

	var $table_name = 'content_image';
	var $id_field = 'ci_id';
	var $status_field = 'ci_status';
	var $entry_field = 'ci_entry';
	var $stamp_field = 'ci_stamp';
	var $deletion_field = 'ci_deletion';
	var $order_field = 'ci_entry';
	var $order_dir = 'DESC';
	var $label_field = 'ci_name';

	var $search_in = array('ci_name');

	var $menu_group = 'setup';
	
	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->sci->assign('menu_group' , $this->menu_group);

	}

	function enum_setting($maindata=array()) { 
		return $maindata;
	}

	function join_setting() {
		$this->db->join('content' , 'content.c_id = content_image.c_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('content.b_id' , $this->branch_id);
	}

	function validation_setting() {
		$this->form_validation->set_rules('c_id', 'content', 'trim|xss_clean');
		$this->form_validation->set_rules('ci_order', 'Order', 'trim|numeric|xss_clean'); 
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id ); 
		$this->db->set('c_id' , $this->input->post('c_id') );
		$this->db->set('ci_order' , $this->input->post('ci_order') );
		
		$this->image_directory = 'userfiles/upload/';
		$this->thumb_directory = 'userfiles/upload/thumb/';
		$this->thumb_width = 200;
		$this->thumb_height = 200;
		if($_FILES['ci_image']['name'] != '' ) {
			$filename = $this->_upload_image('ci_image', TRUE);
			$this->db->set('ci_image' , $filename);
		}
	}



	function pre_add_edit() {
		$this->db->where('cl_code' , 'page');
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->db->where('c_status' , 'Active');
		$this->db->where('cl_id' , $content_label['cl_id']);
		$res = $this->db->get('content');
		$all_content = $res->result_array();
		$this->sci->assign('all_content' , $all_content);
	}

	function pre_add() { 
		
	}

	function pre_edit($id=0) {
		//$this->db->where('ci_status' , 'Active');
		//$this->db->where('ci_id !=' , $id);
		//$res = $this->db->get('content_label');
		//$all_parent = $res->result_array();
		//$this->sci->assign('all_parent' , $all_parent);
	}



}
