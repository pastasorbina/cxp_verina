<?php
class Gallery_album extends MY_Controller {

	//var $module = 'page';
	var $mod_title = 'Manage Album';
	var $available_position = array();
	var $option = array();

	var $table_name = 'gallery_album';
	var $id_field = 'ga_id';
	var $status_field = 'ga_status';
	var $entry_field = 'ga_entry';
	var $stamp_field = 'ga_stamp';
	var $deletion_field = 'ga_deletion';
	var $order_field = 'ga_entry';

	var $search_in = array('ga_name');
	
	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';

	var $thumb_width = 200;
	var $thumb_height = 134;


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->load->model('mod_content');
		$this->load->model('mod_media');
		$this->load->model('mod_media_relation');
		$this->load->model('mod_media_category');

		$this->available_position[] = array('pos'=>'image', 'name'=>'Image');
	}


	function index( $pagelimit=10, $offset=0, $orderby='ga_entry', $ascdesc='DESC', $encodedkey='' ) {
		$this->session->set_bread('list');
		if($orderby == '') { $orderby = $this->order_field; }

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->db->start_cache();
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where( $this->status_field , 'Active' );
		$this->db->order_by($orderby , $ascdesc);
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			foreach($this->search_in as $k=>$tmp) {
				$this->db->or_like($tmp, $searchkey);
			}
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->stop_cache();

		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."view_list/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			foreach($this->available_position as $l=>$tmp2) {
				$result = $this->mod_media->get_media($this->mod, $tmp2['pos'], $tmp['ga_id']);
				$maindata[$k][$tmp2['pos']] = $result;
			}
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('index.htm');
	}

	function add() {

		$this->load->library('form_validation');
		$this->_set_rules();

		if($this->form_validation->run() == FALSE ) {
			//assign available position
			$this->sci->assign('available_position' , $this->available_position);
			$this->sci->da($this->template_add);
		}else{
			$this->_set_db();
			$this->db->set('ga_entry' , date('Y-m-d H:i:s') );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();
			$this->_insert_media($insert_id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				redirect($this->mod_url."index" );
			}

		}
	}

	function edit($id=0) {
		//get data from history's last update
		$this->db->where('ga_id' , $id);
		$this->db->where('ga_status' , 'Active' );
		$this->db->order_by('ga_entry' , 'DESC');
		$res = $this->db->get('gallery_album');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');

		if($this->form_validation->run() == FALSE ) {
			//assign available position
			$position = $this->_get_media($this->available_position, $id);
			$this->sci->assign('available_position' , $position);
			$this->sci->da($this->template_edit);
		}else{
			$this->_set_db();
			$this->db->where('ga_id' , $id);
			$ok = $this->db->update($this->table_name);
			$this->_insert_media($id);
			if(!$ok) { $this->session->set_confirm(0); } else { $this->session->set_confirm(1); }
			redirect($this->mod_url."index" );
		}
	}


	function _set_rules() {
		$this->form_validation->set_rules('ga_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');
	}

	function _set_db() {
		$this->db->set('b_id' , $this->branch_id );
		$this->db->set('ga_name' ,  $this->input->post('ga_name') );
	}



}
