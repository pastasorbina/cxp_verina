<?php
class Sitemap extends MY_Controller {

	var $mod_title = 'Manage Sitemap';

	var $table_name = 'banner';
	var $id_field = 'bn_id';
	var $status_field = 'bn_status';
	var $entry_field = 'bn_entry';
	var $stamp_field = 'bn_stamp';
	var $deletion_field = 'bn_deletion';
	var $order_field = 'bn_entry';
	var $order_dir = 'DESC';
	var $label_field = 'bn_title';

	var $search_in = array('bn_title', 'bn_desc');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		//$this->sci->assign('use_ajax' , TRUE);


	}

	function index() {

		$filename = 'text.txt';
		$somecontent = "Add this to the file\n";

		// Let's make sure the file exists and is writable first.
		if (is_writable($filename)) {

			// In our example we're opening $filename in append mode.
			// The file pointer is at the bottom of the file hence
			// that's where $somecontent will go when we fwrite() it.
			if (!$handle = fopen($filename, 'a')) {
				 echo "Cannot open file ($filename)";
				 exit;
			}

			// Write $somecontent to our opened file.
			if (fwrite($handle, $somecontent) === FALSE) {
				echo "Cannot write to file ($filename)";
				exit;
			}

			echo "Success, wrote ($somecontent) to file ($filename)";

			fclose($handle);

		} else {
			echo "The file $filename is not writable";
		}
	}

	function enum_setting($maindata=array()) {

		return $maindata;
	}

	function join_setting() {
		$this->db->join('user' , 'user.u_id = banner.u_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('banner.b_id' , $this->branch_id);
	}

	function validation_setting() {
		$this->form_validation->set_rules('bn_title', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('bn_desc', 'Desc', 'trim');
		$this->form_validation->set_rules('bn_url', 'URL', 'trim');
		$this->form_validation->set_rules('bn_order', 'Order', 'trim|numeric');
		$this->form_validation->set_rules('bn_date', 'Date', 'trim');
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id );
		$this->db->set('u_id' , $this->userinfo['u_id'] );

		$bn_title = $this->input->post('bn_title');
		$this->db->set('bn_title' , $bn_title );

		$this->db->set('bn_desc' , $this->input->post('bn_desc'));
		$this->db->set('bn_url' , $this->input->post('bn_url'));
		$this->db->set('bn_order' , $this->input->post('bn_order'));

		$bn_date = $this->input->post('bn_date');
		$bn_date = $bn_date ? $bn_date : date('Y-m-d H:i:s');
		$this->db->set('bn_date' , $bn_date);

		$this->image_directory = 'userfiles/banner/';
		$this->thumb_directory = 'userfiles/banner/thumb/';
		$this->thumb_width = 80;
		$this->thumb_height = 80;
		if($_FILES['bn_image']['name'] != '') {
			$filename = $this->_upload_image('bn_image');
			$this->db->set('bn_image' , $filename);
		}
	}


	function pre_add_edit() {
		$this->config->set_item('global_xss_filtering', FALSE);
	}

	function pre_add() {
		//$this->_get_all_parent();
	}

	function pre_edit($id=0) {
		//$this->db->where('bn_id !=' , $id);
		//$this->_get_all_parent();
	}



}
