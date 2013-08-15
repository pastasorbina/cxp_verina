<?php

class User_role_key extends MY_Controller {

	var $mod_title = 'User Role Key';

	var $table_name = 'user_role_key';
	var $id_field = 'urk_id';
	var $status_field = '';
	var $entry_field = '';
	var $stamp_field = '';
	var $deletion_field = '';
	var $order_field = '';
	var $order_dir = 'DESC';
	var $label_field = '';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('USER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);
		$this->userinfo = $this->session->get_userinfo();
	}


	function index($pagelimit=100, $offset=0, $orderby='urk_key', $ascdesc='ASC', $encodedkey='') {
		$this->session->set_bread('list');
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get();
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$maindata = $this->iteration_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function database_setter($action) {
		$key = $this->input->post('urk_key');
		$this->load->helper('inflector');
		$key = strtoupper(underscore(trim($key)));
		print $key;
		$this->db->set('b_id' , $this->branch_id);
		$this->db->set('urk_key' , $key);
		$this->db->set('urk_desc' , $this->input->post('urk_desc') );
	}

	function validation_setting($action) {
		$this->form_validation->set_rules('urk_key', 'Key', 'trim|required');
		$this->form_validation->set_rules('urk_desc', 'Desc', 'trim|xss_clean');
	}

	function add() {
		$this->sci->assign('ajax_action' , 'add');

		$this->load->library('form_validation');
		$this->validation_setting('add');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('edit.htm');
		}else{
			$this->database_setter('edit');
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();
			$this->post_add($insert_id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				//redirect($this->session->get_bread('list') );
				redirect($this->mod_url.'index');
			}
		}
	}

	function edit($urk_id=0) {
		$this->sci->assign('ajax_action' , 'edit');
		$this->db->where('urk_id' , $urk_id);
		$res = $this->db->get('user_role_key');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->validation_setting('edit');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('edit.htm');
		}else{
			$this->database_setter('edit');
			$this->db->where('urk_id' , $urk_id);
			$ok = $this->db->update($this->table_name);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'edit');
			} else {
				$this->session->set_confirm(1);
				redirect($this->session->get_bread('list') );
			}
		}
	}

	function delete($urk_id=0){
		$this->db->where('urk_id' , $urk_id);
		$ok = $this->db->delete($this->table_name);
		if(!$ok) {
			$this->session->set_confirm(0);
		} else {
			$this->session->set_confirm(1);
		}
		redirect($this->session->get_bread('list') );
	}


}
?>
