<?php

class Config extends MY_Controller {

	var $keys = array();
	var $mod_title = 'Site Configuration';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('ADMIN'), 'admin');
	}

	function index($c_cat='site') {
		$this->sci->assign('c_cat' , $c_cat);

		$this->load->library('form_validation');
		$this->validation_setting();

		if($this->form_validation->run() == FALSE ) {
			//get categories
			$this->db->order_by('c_order' , 'ASC');
			$this->db->where('b_id' , $this->branch_id);
			$this->db->group_by('c_cat');
			$res = $this->db->get('config');
			$category = $res->result_array();
			$this->sci->assign('category' , $category);

			$this->db->order_by('c_order' , 'ASC');
			$this->db->where('c_type' , 'config');
			$this->db->where('c_cat' , $c_cat);
			$this->db->where('b_id' , $this->branch_id);
			$res = $this->db->get('config');
			$config_data = $res->result_array();
			$this->sci->assign('config_data' , $config_data);

			$this->sci->da('edit.htm');
		}else{
			$this->db->trans_start();
			$c_text_key = $this->input->post('c_text_key');
			$c_text_value = $this->input->post('c_text_value');
			if(is_array($c_text_key)) {
				foreach($c_text_key as $k=>$tmp) {
					if(isset($c_text_value[$k])) {
						$this->db->where('c_key' , $tmp);
						$this->db->where('b_id' , $this->branch_id);
						$this->db->where('c_type' , 'config');
						$this->db->set('c_value' , $c_text_value[$k]);
						$this->db->update('config');
					}
				}
			}

			$c_image_key = $this->input->post('c_image_key');
			$c_image_value = $this->input->post('c_image_value');
			$this->image_directory = './userfiles/config/';
			$this->thumb_directory = './userfiles/config/thumb/';
			$this->thumb_width = 125;
			$this->thumb_height = 125;

			if(is_array($c_image_key)) {
				foreach($c_image_key as $k=>$tmp) {
					$image_remove = $this->input->post($tmp.'_remove');
					if($image_remove) {
						$this->db->set('c_value' , '');
					} else {
						if($_FILES[$tmp]['name'] != '') {
							$filename = $this->_upload_image($tmp);
						} else {
							$filename = $_POST[$tmp.'_default'];
						}
						$this->db->set('c_value' , $filename);
					}
					$this->db->where('c_key' , $tmp);
					$this->db->where('b_id' , $this->branch_id);
					$this->db->where('c_type' , 'config');

					$this->db->update('config');
				}
			}

			$this->db->trans_complete();
			if( $this->db->trans_status() != FALSE ) {
				$this->session->set_confirm(1);
			} else {
				$this->session->set_confirm(0);
			}
			redirect($this->mod_url."index/$c_cat");
		}
	}


	function index2($c_cat='site') {
		$this->sci->assign('c_cat' , $c_cat);

		$this->load->library('form_validation');
		$this->validation_setting();
		if($this->form_validation->run() == FALSE ) {

			$this->db->order_by('c_order' , 'ASC');
			$this->db->where('b_id' , $this->branch_id);
			$this->db->group_by('c_cat');
			$res = $this->db->get('config');
			$category = $res->result_array();
			foreach($category as $k=>$tmp) {
				$this->db->order_by('c_order' , 'ASC');
				$this->db->where('c_type' , 'config');
				$this->db->where('c_cat' , $tmp['c_cat']);
				$this->db->where('b_id' , $this->branch_id);
				$res = $this->db->get('config');
				$config = $res->result_array();
				$category[$k]['config'] = $config;
			}
			$this->sci->assign('category' , $category);


			$this->sci->da('edit.htm');
		}else{
			$this->db->trans_start();
			$c_text_key = $this->input->post('c_text_key');
			$c_text_value = $this->input->post('c_text_value');
			if(is_array($c_text_key)) {
				foreach($c_text_key as $k=>$tmp) {
					if(isset($c_text_value[$k])) {
						$this->db->where('c_key' , $tmp);
						$this->db->where('b_id' , $this->branch_id);
						$this->db->where('c_type' , 'config');
						$this->db->set('c_value' , $c_text_value[$k]);
						$this->db->update('config');
					}
				}
			}

			$c_image_key = $this->input->post('c_image_key');
			$c_image_value = $this->input->post('c_image_value');
			$this->image_directory = 'userfiles/config/';
			$this->thumb_directory = 'userfiles/config/thumb/';
			$this->thumb_width = 125;
			$this->thumb_height = 125;

			if(is_array($c_image_key)) {
				foreach($c_image_key as $k=>$tmp) {

					if($_FILES[$tmp]['name'] != '') {
						$filename = $this->_upload_image($tmp);
					} else {
						$filename = $_POST[$tmp.'_default'];
					}
					$this->db->where('c_key' , $tmp);
					$this->db->where('b_id' , $this->branch_id);
					$this->db->where('c_type' , 'config');
					$this->db->set('c_value' , $filename);
					$this->db->update('config');
				}
			}

			$this->db->trans_complete();
			if( $this->db->trans_status() != FALSE ) {
				$this->session->set_confirm(1);
			} else {
				$this->session->set_confirm(0);
			}
			redirect($this->mod_url."index/$c_cat");
		}
	}


	function validation_setting() {
		$this->form_validation->set_rules('c_text_key[]', 'key', 'trim|xss_clean');
		$this->form_validation->set_rules('c_text_value[]', 'value', 'trim|xss_clean');
		$this->form_validation->set_rules('c_image_key[]', 'key', 'trim|xss_clean');
		$this->form_validation->set_rules('c_image_value[]', 'value', 'trim|xss_clean');
		$this->form_validation->set_rules('c_cat', 'category', 'trim|xss_clean');
	}

}
?>
