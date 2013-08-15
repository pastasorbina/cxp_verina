<?php
class Area_city extends MY_Controller {

	var $mod_title = 'Manage city';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'area_city';
	var $id_field = 'ac_id';
	var $status_field = 'ac_status';
	var $entry_field = 'ac_entry';
	var $stamac_field = 'ac_stamp';
	var $deletion_field = 'ac_deletion';
	var $order_field = 'ac_entry';

	var $author_field = 'ac_author';
	var $editor_field = 'ac_editor';

	var $search_in = array('ac_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}

	function index($ap_id=0, $pagelimit=10, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {

		$this->db->where('ap_id' , $ap_id);
		$res = $this->db->get('area_province');
		$province = $res->row_array();
		$this->sci->assign('province' , $province);

		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
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
			$this->join_setting();
			$this->where_setting();
			$this->db->where('area_city.ap_id' , $ap_id);
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$ap_id". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('area_province' , 'area_province.ap_id = area_city.ap_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('ac_name', 'Title', 'trim|xss_clean');
	}

	function database_setter() {
		$this->db->set('ap_id' ,  $this->input->post('ap_id'));
		$this->db->set('ac_name' ,  $this->input->post('ac_name'));
	}

	function ajax_add($ap_id=0) {
		$this->db->where('ap_id' , $ap_id);
		$res = $this->db->get('area_province');
		$province = $res->row_array();
		$this->sci->assign('province' , $province);
		$this->ajax_add_edit('add');
	}

	function pre_add_edit() { }
	function pre_add() { }
	function pre_edit($id=0) { }


	function add_multiple($ap_id=0) {
		$this->db->where('ap_id' , $ap_id);
		$res = $this->db->get('area_province');
		$province = $res->row_array();
		$this->sci->assign('province' , $province);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('ap_id', 'Ap ID', 'trim|xss_clean');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('add_multiple.htm');
		}else{
			$filterme = $this->input->post('filterme');
			$subject = $filterme;
			$pattern = '/^def/';
			$pattern = '\[start\](.*?)\[end\]';
			preg_match($pattern, $subject, $matches);
			print_r($matches);
			exit();
			$this->database_setter('add');
			$this->db->set($this->entry_field , date('Y-m-d H:i:s') );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();
			$this->post_add($insert_id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				redirect($this->session->get_bread('list') );
			}
		}
	}


}
