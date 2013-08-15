<?php

class Site_config extends MY_Controller {

	var $keys = array();
	var $mod_title = 'Site Configuration';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array(), 'admin');

		//get configs
		$res = $this->db->get('config');
		$config = $res->result_array();
		$data = array();
		foreach($config as $k=>$tmp) {
			$data[$tmp['c_key']] = $tmp['c_value'];
		}
		$this->sci->assign('config' , $config);
		$this->sci->assign('data' , $data);

		$this->config->set_item('global_xss_filtering', FALSE);
	}

	function load_sidebar() {
		$sidebar = $this->sci->fetch('admin/site_config/sidebar.htm');
		$this->sci->assign('sidebar' , $sidebar);
	}

	function index() {
		$this->site();
	}

	function site() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('config[]','','trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('currpage' , 'site');
			$this->load_sidebar();
			$this->sci->da('site.htm');
		} else {
			$config = $this->input->post('config');
			foreach($config as $k=>$tmp) {
				$this->db->where('c_key' , $k);
				$this->db->set('c_value' , $tmp);
				$this->db->update('config');
			}
			$this->session->set_confirm(1);
			redirect($this->mod_url."site");
		}
	}

	function email_support() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('config[]','','trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('currpage' , 'email');
			$this->load_sidebar();
			$this->sci->da('email_support.htm');
		} else {
			$config = $this->input->post('config');
			foreach($config as $k=>$tmp) {
				$this->db->where('c_key' , $k);
				$this->db->set('c_value' , $tmp);
				$this->db->update('config');
			}
			$this->session->set_confirm(1);
			redirect($this->mod_url."email_support");
		}
	}

	function ecommerce() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('config[]','','trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('currpage' , 'email');
			$this->load_sidebar();
			$this->sci->da('ecommerce.htm');
		} else {
			$config = $this->input->post('config');
			foreach($config as $k=>$tmp) {
				$this->db->where('c_key' , $k);
				$this->db->set('c_value' , $tmp);
				$this->db->update('config');
			}
			$this->session->set_confirm(1);
			redirect($this->mod_url."ecommerce");
		}
	}

	function elements() {

		$this->load->library('form_validation');
		$this->form_validation->set_rules('image[]','','trim|xss_clean');
		$this->form_validation->set_rules('image_remove[]','','trim|xss_clean');
		$this->form_validation->set_rules('image_default[]','','trim|xss_clean');
		$this->form_validation->set_rules('config[]','','trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('currpage' , 'elements');
			$this->load_sidebar();
			$this->sci->da('elements.htm');
		} else {
			$image = $this->input->post('image');
			$image_remove = $this->input->post('image_remove');
			$image_default = $this->input->post('image_default');
			$config = $this->input->post('config');

			foreach($image_default as $k=>$tmp) {
				$this->image_directory = './userfiles/config/';
				$this->thumb_directory = './userfiles/config/thumb/';
				$this->thumb_width = 125;
				$this->thumb_height = 125;

				if($image_remove[$k] != '') {
					$this->db->set('c_value' , '');
				} else {
					if($_FILES[$k]['name'] != '') {
						$filename = $this->_upload_image($k);
					} else {
						$filename = $image_default[$k];
					}
					$this->db->set('c_value' , $filename);
				}
				$this->db->where('c_key' , $k);
				$this->db->update('config');
			}

			foreach($config as $k=>$tmp) {
				$this->db->where('c_key' , $k);
				$this->db->set('c_value' , $tmp);
				$this->db->update('config');
			}

			$this->session->set_confirm(1);
			redirect($this->mod_url."elements");
		}
	}




	function newsletter() {
		$this->load->library('form_validation');
		$this->form_validation->set_rules('config[]','','trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('currpage' , 'newsletter');
			$this->load_sidebar();
			$this->sci->da('newsletter.htm');
		} else {
			$config = $this->input->post('config');
			foreach($config as $k=>$tmp) {
				$this->db->where('c_key' , $k);
				$this->db->set('c_value' , $tmp);
				$this->db->update('config');
			}
			$this->session->set_confirm(1);
			redirect($this->mod_url."newsletter");
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
