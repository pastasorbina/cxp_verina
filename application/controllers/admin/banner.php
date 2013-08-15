<?php
class Banner extends MY_Controller {

	var $mod_title = 'Manage Banners';

	var $table_name = 'banner';
	var $id_field = 'bn_id';
	var $status_field = 'bn_status';
	var $entry_field = 'bn_entry';
	var $stamp_field = 'bn_stamp';
	var $deletion_field = 'bn_deletion';
	var $order_field = 'bn_order';
	var $order_dir = 'ASC';
	var $label_field = 'bn_title';

	var $search_in = array('bn_title', 'bn_desc');

	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('BANNER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->image_directory = 'userfiles/upload/';
		$this->thumb_directory = 'userfiles/upload/thumb/';
		$this->thumb_width = 80;
		$this->thumb_height = 80;
		$this->userinfo = $this->session->get_userinfo(); 
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
		$this->form_validation->set_rules('bn_title', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_desc', 'Desc', 'trim');
		$this->form_validation->set_rules('bn_url', 'URL', 'trim');
		$this->form_validation->set_rules('bn_order', 'Order', 'trim|numeric');
		$this->form_validation->set_rules('bn_caption', 'Caption', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_display_caption', 'Display Caption', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_start_date', 'Start', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_end_date', 'End', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_type', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_id', 'Promo', 'trim|xss_clean');
		//$this->form_validation->set_rules('bn_image_from_pr', 'IMage From Promo', 'trim|xss_clean');
		//$this->form_validation->set_rules('bn_date', 'Date', 'trim|xss_clean');
	}

	function database_setter($action) {
		if($action == 'add'){
			$this->db->set('bn_author' , $this->userinfo['u_id'] );
		}

		$this->db->set('bn_author' , $this->userinfo['u_id'] );
		$this->db->set('bn_title' , $this->input->post('bn_title') );
		$this->db->set('bn_desc' , $this->input->post('bn_desc'));
		$this->db->set('bn_url' , $this->input->post('bn_url'));
		$this->db->set('bn_order' , $this->input->post('bn_order'));
		$this->db->set('bn_caption' , $this->input->post('bn_caption'));
		
		if($this->input->post('bn_type') == 'Timed') {
			$this->db->set('bn_start_date' , $this->input->post('bn_start_date'));
			$this->db->set('bn_end_date' , $this->input->post('bn_end_date'));
			$this->db->set('pr_id' , $this->input->post('pr_id'));
		} 
		$this->db->set('bn_type' , $this->input->post('bn_type'));
		if($this->input->post('bn_caption')) {
			$bn_display_caption = "Yes";
		} else {
			$bn_display_caption = "No";
		}
		$this->db->set('bn_display_caption' , $bn_display_caption);
		

		//$bn_date = $this->input->post('bn_date');
		//$bn_date = ($bn_date!='') ? $bn_date : date('Y-m-d H:i:s');
		//$this->db->set('bn_date' , $bn_date);

		if($_FILES['bn_image']['name'] != '') {
			$this->db->set('bn_image' , $this->_upload_image('bn_image'));
		} else {
			//if( $this->input->post('bn_image_from_pr') != '') {
				//$this->db->set('bn_image' , $this->input->post('bn_image_from_pr'));
			//}
		}

	}


	function pre_add_edit() {
		$this->config->set_item('global_xss_filtering', FALSE);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}



}
