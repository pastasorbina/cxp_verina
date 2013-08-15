<?php

class Form extends MY_Controller {

	var $mod_title = 'Manage Forms';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
	}

	function index($orderby = 'f_id' , $ascdesc = 'ASC' , $page_number = 0 , $searchkey = '') {
		$this->session->set_bread('list');

		// Get form database
		$this->db->from('form');
		$this->db->where('f_status' ,"Active");
		$this->db->where('b_id' , $this->branch_id );

		$res = $this->db->get();
		$this->sci->assign('maindata' , $res->result_array());

		// Form Validation class
		$this->load->library('form_validation');
		$this->form_validation->set_rules('f_name' , 'Name' , 'trim|strip_tags|required');
		$this->form_validation->set_rules('f_destination_emails' , 'Destination Emails' , 'trim|strip_tags|valid_emails');
		$this->form_validation->set_rules('f_header_text' , 'Header Text' , '');
		$this->form_validation->set_rules('f_footer_text' , 'Footer Text' , '');

		if ($this->form_validation->run() == FALSE) {
			$this->sci->da('index.htm');
		}
		else {
		$this->db->
			set('f_name' , set_value('f_name'))->
			set('f_destination_emails' , set_value('f_destination_emails'))->
			set('f_header_text' , $this->input->post('f_header_text'))->
			set('f_footer_text' , $this->input->post('f_footer_text'))->
			set('b_id', $this->branch_id)->
			insert('form');

			$ii = $this->db->insert_id();

			// Insert all other information
			$uniq = $this->input->post('uniq');
			$key = $this->input->post('key');
			$type = $this->input->post('type');
			$regex = $this->input->post('regex');
			$options = $this->input->post('options');
			$required = $this->input->post('required');

			if ($uniq) {
				foreach($uniq as $k) {
					if (trim($key[$k]) != '') {
						$this->db->
							set('f_id' , $ii)->
							set('fd_key' , trim($key[$k]))->
							set('fd_type' , $type[$k])->
							set('fd_regex' , trim($regex[$k]))->
							set('fd_options' , trim($options[$k]))->
							set('fd_req' , (isset($required[$k])?'True' : 'False'))->
							insert('form_detail');
					}
				}
			}

			redirect($this->mod_url."index");
		}
	}

	function delete($f_id = 0) {
		// Delete data
		$this->db->where('f_id' , $f_id);
		$this->db->set('f_status' , 'Deleted');
		$this->db->update('form');

		redirect($this->mod_url."index");
	}

	function edit($f_id = 0) {
		// Get Form information
		$res = $this->db->
			where('f_id' , $f_id)->
			where('f_status' ,"Active")->
			get('form');
		if ($row = $res->row()) {
			$this->sci->assign('maindata' , $row);
			$maindata = $row;
		}
		else {
			redirect($this->mod_url."index");
		}

		// Get form detail
		$res = $this->db->
			where('f_id' , $f_id)->
			order_by('fd_id')->
			get('form_detail');
		$this->sci->assign('form_detail' , $res->result_array());

		// Form type definition
		$form_type = array(
			'TEXT' => 'Text',
			'TEXTAREA' => 'Text Area',
			'RADIO' => 'Radio',
			'SELECT' => 'Select',
			'CHECKBOX' => 'Checkbox',
			'FILE' => 'File'
		);
		$this->sci->assign('form_type' , $form_type);

		// Form Validation class
		$this->load->library('form_validation');
		$this->form_validation->set_rules('f_name' , 'Name' , 'trim|strip_tags|required');
		$this->form_validation->set_rules('f_destination_emails' , 'Destination Emails' , 'trim|strip_tags|valid_emails');
		$this->form_validation->set_rules('f_header_text' , 'Header Text' , '');
		$this->form_validation->set_rules('f_footer_text' , 'Footer Text' , '');
 
		if ($this->form_validation->run() == FALSE) {
			$this->sci->da('edit.htm');
		} else {

		$this->db->
			set('f_name' , set_value('f_name'))->
			set('f_destination_emails' , set_value('f_destination_emails'))->
			set('f_header_text' , $this->input->post('f_header_text'))->
			set('f_footer_text' , $this->input->post('f_footer_text'))->
			where('f_id' , $f_id)->
			update('form');

			// Delete all data 1st
			$this->db->
				where('f_id' , $f_id)->
				delete('form_detail');

			// Insert all other information
			$uniq = $this->input->post('uniq');
			$key = $this->input->post('key');
			$type = $this->input->post('type');
			$regex = $this->input->post('regex');
			$options = $this->input->post('options');
			$required = $this->input->post('required');

			if ($uniq) {
				foreach($uniq as $k) {
					if (trim($key[$k]) != '') {
						$this->db->
							set('f_id' , $f_id)->
							set('fd_key' , trim($key[$k]))->
							set('fd_type' , $type[$k])->
							set('fd_regex' , trim($regex[$k]))->
							set('fd_options' , trim($options[$k]))->
							set('fd_req' , (isset($required[$k])?'True' : 'False'))->
							insert('form_detail');
					}
				}
			}

			redirect($this->mod_url."index");
		}
	}

	function add_new_field() {
		// Form type definition
		$form_type = array(
			'TEXT' => 'Text',
			'TEXTAREA' => 'Text Area',
			'RADIO' => 'Radio',
			'SELECT' => 'Select',
			'CHECKBOX' => 'Checkbox',
			'FILE' => 'File'
		);
		$this->sci->assign('form_type' , $form_type);

		$this->sci->assign('uniq' , uniqid());
		$this->sci->d('add_new_field.htm');
	}

}

?>
