<?php
class Gallery extends MY_Controller {

	//var $module = 'page';
	var $mod_title = 'Manage Gallery';
	var $available_position = array();
	var $option = array();

	var $table_name = 'gallery';
	var $id_field = 'g_id';
	var $status_field = 'g_status';
	var $entry_field = 'g_entry';
	var $stamp_field = 'g_stamp';
	var $deletion_field = 'g_deletion';
	var $order_field = 'g_date';

	var $search_in = array('g_title', 'g_desc');

	var $thumb_width = 200;
	var $thumb_height = 134;
	
	var $default_pagelimit = 10;
	
	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin'); 
  
		
		$this->db->where('ga_status' , 'Active');
		$res = $this->db->get('gallery_album');
		$all_album = $res->result_array();
		$this->sci->assign('all_album' , $all_album);

	}
	
	function join_setting(){
		$this->db->join('gallery_album ga' , 'ga.ga_id = gallery.ga_id' , 'left');
		$this->db->join('user' , 'user.u_id = gallery.g_author_id' , 'left');
	}
	
	function where_setting(){
		
	}
	
	function pre_add_edit(){
		
	}
	
	function enum_setting($maindata=array()) { 
		
		return $maindata;
	}
 
	function validation_setting() {
		$this->form_validation->set_rules('g_title', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('g_desc', 'Desc', 'trim');
		$this->form_validation->set_rules('g_date', 'Date', 'trim');
		$this->form_validation->set_rules('ga_id', 'Gallery Album', 'trim|required');

		$this->form_validation->set_rules('g_type', 'Type', 'trim');
		$this->form_validation->set_rules('g_data', 'Data', 'trim');  
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id );
		$this->db->set('ga_id' ,  $this->input->post('ga_id') );
		$this->db->set('g_title' , $this->input->post('g_title'));
		$this->db->set('g_desc' , $this->input->post('g_desc') );

		$g_type = $this->input->post('g_type');
		$this->db->set('g_type' , $g_type);
		
		switch($g_type) {
			case 'image' :
				$this->image_directory = 'userfiles/upload/';
				$this->thumb_directory = 'userfiles/upload/thumb/';
				$this->thumb_width = 200;
				$this->thumb_height = 200;
				if($_FILES['g_data']['name'] != '' ) {
					$filename = $this->_upload_image('g_data', TRUE);
					$this->db->set('g_data' , $filename);
				}
				//print $filename;
				//print_r($_FILES);
				//exit();
			//case 'video' :
			//	$this->db->set('g_data' , $this->input->post('g_data'));
			//	break;
			//case 'file' :
			//	if($_FILES['g_data_file']['name'] != '' ) {
			//		$config['upload_path'] = 'userfiles/file/';
			//		$config['allowed_types'] = 'pdf|doc|jpg|gif|png|';
			//		//$config['max_size']	= '10000000000000000000000';
			//		$config['file_name'] = sanitize_filename(url_title(remove_symbols($_FILES['g_data_file']['name'])));
			//		$this->load->library('upload');
			//		$this->upload->initialize($config);
			//
			//		$this->upload->do_upload('g_data_file');
			//		$uploaded = $this->upload->data();
			//		$filename = $uploaded['raw_name'].$uploaded['file_ext'];
			//		$this->db->set('g_data' , $filename);
			//	} 
			//	break;
			default :
				break;
		}


		$g_date = $this->input->post('g_date');
		$g_date = $g_date ? $g_date : date('Y-m-d H:i:s');
		$this->db->set('g_date' , $g_date );
	}




}
