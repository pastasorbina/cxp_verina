<?php
class Shipping_template extends MY_Controller {

	var $mod_title = 'Manage Shipping Template';
	//var $available_position = array();
	//var $default_option = array();
	//
	//var $table_name = 'shipping_area';
	//var $id_field = 'sa_id';
	//var $status_field = 'sa_status';
	//var $entry_field = 'sa_entry';
	//var $stamsa_field = 'sa_stamp';
	//var $deletion_field = 'sa_deletion';
	//var $order_field = 'sa_entry';
	//
	//var $author_field = 'sa_author';
	//var $editor_field = 'sa_editor';
	//
	//var $search_in = array('sa_name');
	//
	//var $template_index = "index.htm";
	//var $template_add = "edit.htm";
	//var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('SHIPPING_MANAGE_METHOD'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}

	function index() {
		$this->session->set_bread('list');
		
		$this->db->where('st_status' , 'Active');
		$res = $this->db->get('shipping_template');
		$maindata = $res->result_array();
		
		foreach($maindata as $k=>$tmp) {
			$st_ac_id = $tmp['st_ac_id'];
			$st_ac_id_list = unserialize($st_ac_id);
			$maindata[$k]['numofarea'] = sizeof($st_ac_id_list);
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->da('index.htm');
	}
	
	function pre_add_edit() {
		$this->load->model('mod_area');
		$area_province = $this->mod_area->get_all_province();
		$this->sci->assign('area_province' , $area_province);
	}
	
	function add() {
		$this->pre_add_edit();
		$this->sci->assign('action' , 'add');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('st_name','','required|trim|xss_clean');
		$this->form_validation->set_rules('list_freeship_area[]', 'Area', 'trim|xss_clean');
		
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('add.htm');
		} else {
			$list_area = $this->input->post('list_freeship_area');
			if(sizeof($list_area) > 0) {
				$this->db->set('st_ac_id' , serialize($list_area) );
			} else {
				$this->db->set('st_ac_id' , '');
			}
			
			$this->db->set('st_name' , $this->input->post('st_name') );
			$this->db->set('st_code' , $this->input->post('st_name') );
			$this->db->set('st_entry' , 'NOW()', FALSE);
			$this->db->insert('shipping_template');
			
			$this->session->set_confirm(1);
			redirect($this->session->get_bread('list'));
		}
		
	}
	
	function edit($id=0) {
		$this->pre_add_edit();
		$this->sci->assign('action' , 'edit');
		
		$this->db->where('st_id' , $id);
		$res = $this->db->get('shipping_template');
		$data = $res->row_array();
		$area = $data['st_ac_id'];
		$area = unserialize($area);
		$selected_area = array();
		if(is_array($area)) {
			foreach($area as $k=>$tmp) {
				$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
				$this->db->where('ac_id' , $tmp);
				$res = $this->db->get('area_city');
				$city = $res->row_array();
				$selected_area[$k] = $city;
			}
		}  
		$this->sci->assign('selected_freeship_area' , $selected_area);
		$this->sci->assign('data' , $data);
		
		$this->load->library('form_validation');
		$this->form_validation->set_rules('st_name','','required|trim|xss_clean');
		$this->form_validation->set_rules('list_freeship_area[]', 'Area', 'trim|xss_clean');
		
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('add.htm');
		} else {
			$list_area = $this->input->post('list_freeship_area');
			if(sizeof($list_area) > 0) {
				$this->db->set('st_ac_id' , serialize($list_area) );
			} else {
				$this->db->set('st_ac_id' , '');
			}
			
			$this->db->set('st_name' , $this->input->post('st_name') );
			$this->db->set('st_code' , $this->input->post('st_name') );
			$this->db->where('st_id' , $id);
			$this->db->update('shipping_template');
			
			$this->session->set_confirm(1);
			redirect($this->session->get_bread('list'));
		}
		
	}
	
	function delete($id) {
		$this->db->where('st_id' , $id);
		$this->db->set('st_status' , 'Deleted');
		$this->db->set('st_deletion' , 'NOW()', FALSE);
		$this->db->update('shipping_template');
		$this->session->set_confirm(1);
		redirect($this->session->get_bread('list'));
	}
	
	
	
	function ajax_freeship_selected($br_id=0){
		$this->sci->d('ajax_freeship_selected.htm');
	}

	function ajax_get_city_selection($ap_id=0){
		$this->load->model('mod_area');
		$city = $this->mod_area->get_all_city_by_province($ap_id);
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_city_selection.htm');
	}

	function ajax_add_freeship($ac_id=0){
		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
		$this->db->where('ac_id' , $ac_id);
		$this->db->where('ac_status' , 'Active');
		$res = $this->db->get('area_city');
		$city = $res->row_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_add_freeship.htm');
	}

	function ajax_add_freeship_by_province($ap_id=0){
		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
		$this->db->where('area_city.ap_id' , $ap_id);
		$this->db->where('ac_status' , 'Active');
		$res = $this->db->get('area_city');
		$city = $res->result_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_add_freeship_by_province.htm');
	}
	
	function search_city() {
		$searchkey = $this->input->post('searchkey');
		$searchkey = trim($searchkey);
		$ap_id = $this->input->post('ap_id');
		$this->db->start_cache();
		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left'); 
		if($ap_id != 0) {
			$this->db->where('area_city.ap_id' , $ap_id);
		}
		if($searchkey != '') {
			$this->db->like('area_city.ac_name' , $searchkey);
			$this->db->or_like('area_city.ac_code' , $searchkey );
		}
		
		//$this->db->like('ap.ap_name' , trim($searchkey));
		$this->db->order_by('ac_name' , 'asc');
		$this->db->where('ac_status' , 'Active');
		$this->db->from('area_city');
		$this->db->stop_cache();
		
		$numof = $this->db->count_all_results();
		if($numof >= 300) {
			print "result is too much ($numof) ! refine your search ..";
			$this->db->flush_cache();
			exit();
		}
		
		$res = $this->db->get(); 
		$this->db->flush_cache();
		$city = $res->result_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_search_city.htm');
	}




}
