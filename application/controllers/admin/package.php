<?php
class Package extends MY_Controller {

	var $mod_title = 'Packages';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'package';
	var $id_field = 'pk_id';
	var $status_field = 'pk_status';
	var $entry_field = 'pk_entry';
	var $stampk_field = 'pk_stamp';
	var $deletion_field = 'pk_deletion';
	var $order_field = 'pk_entry';

	var $search_in = array('pk_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->available_position[] = array('pos'=>'image', 'name'=>'Image');
	}



	function index( $pagelimit=10, $offset=0, $orderby='', $ascdesc='DESC', $encodedkey='' ) {
		$this->session->set_bread('list');
		if($orderby == '') {$orderby = $this->order_field; }

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->sci->assign($this->status_field , 'Active' );
		//print $this->mod ;

		$this->db->start_cache();
		//$this->db->join('package_category' , 'package_category.pkc_id = package.pkc_id' , 'left');

		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			if(!empty($this->search_in)) {
				foreach($this->search_in as $k=>$tmp) {
					$this->db->or_like($tmp, $searchkey);
				}
			} else {
				$this->search_setting($searchkey);
			}
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->where($this->status_field , 'Active' );
		$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
		$this->db->order_by($orderbyconv , $ascdesc);
		$this->db->where('package.b_id' , $this->branch_id);
		$this->db->stop_cache();

		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
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
			$this->db->where('pkc_id' , $tmp['pkc_id'] );
			$res = $this->db->get('package_category');
			$category = $res->row_array();
			$maindata[$k]['category'] = $category;

			//get image
			foreach($this->available_position as $l=>$tmp2) {
				$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
				$this->db->where('mr_foreign_id' , $tmp[$this->id_field]);
				$this->db->where('mr_pos' , $tmp2['pos']);
				$this->db->where('mr_status' , 'Active');
				$this->db->order_by('mr_stamp' , 'DESC');
				$res = $this->db->get('media_relation');
				$data = $res->row_array();
				$maindata[$k][$tmp2['pos']] = $data;
			}
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('index.htm');
	}

	function add() {
		$this->load->library('form_validation');
		$this->validation_setting();
		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');

		$this->form_validation->set_rules('po_key[]', 'Option Key', 'trim');
		$this->form_validation->set_rules('po_value[]', 'Option Value', 'trim');

		if($this->form_validation->run() == FALSE ) {
			$this->db->where('pkc_status' , 'Active' );
			$this->db->where('b_id' , $this->branch_id);
			$res = $this->db->get('package_category');
			$all_category = $res->result_array();
			$this->sci->assign('all_category' , $all_category);

			$this->sci->assign('available_position' , $this->available_position);
			$this->sci->da('add.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);

			$this->database_setter();
			$this->db->set($this->entry_field , date('Y-m-d H:i:s') );

			if(!$this->db->insert($this->table_name)) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$insert_id = $this->db->insert_id();
				$this->_insert_media($insert_id);

				$this->session->set_confirm(1);
				redirect($this->mod_url.'index');
			}

		}
	}

	function edit($id=0) {
		$this->db->where('pk_id' , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->db->where('pkc_id' ,$data['pkc_id']);
		$res = $this->db->get('package_category');
		$data['category'] = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->validation_setting();
		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');


		if($this->form_validation->run() == FALSE ) {
			$this->db->where('pkc_status' , 'Active' );
			$this->db->where('b_id' , $this->branch_id);
			$res = $this->db->get('package_category');
			$all_category = $res->result_array();
			$this->sci->assign('all_category' , $all_category);

			$available_position = $this->available_position;
			foreach($available_position as $k=>$tmp) {
				$this->db->where('media.b_id' , $this->branch_id);
				$this->db->where('mr_module' , $this->mod);
				$this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
				$this->db->where('mr_foreign_id' , $id);
				$this->db->where('mr_pos' , $tmp['pos']);
				$res = $this->db->get('media_relation');
				$result = $res->row_array();
				$available_position[$k]['data'] = $result;
			}
			$this->sci->assign('available_position' , $available_position);

			$this->sci->da('edit.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);
			$this->database_setter();
			$this->db->where($this->id_field , $id);
			if( !$this->db->update($this->table_name) ) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'edit');
			} else {
				$this->_insert_media($id);
				$this->session->set_confirm(1);
				redirect($this->mod_url."index" );
			}

		}
	}

	function delete($id=0) {
		$this->change_status($id, 'Deleted');
	}

	function validation_setting() {
		$this->form_validation->set_rules('pk_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pk_desc', 'Description', 'trim');
		$this->form_validation->set_rules('pk_price_text', 'Price Text', 'trim|xss_clean');
		$this->form_validation->set_rules('pk_min_cover', 'Min Coverage', 'trim|xss_clean');
		$this->form_validation->set_rules('pkc_id', 'Category ID', 'trim');
	}

	function database_setter() {
		$this->db->set('b_id' , $this->branch_id);
		$this->db->set('pkc_id' , $this->input->post('pkc_id'));
		$this->db->set('pk_name' , $this->input->post('pk_name') );
		$this->db->set('pk_price_text' , $this->input->post('pk_price_text') );
		$this->db->set('pk_min_cover' , $this->input->post('pk_min_cover') );
		$this->db->set('pk_desc' , $this->input->post('pk_desc') );


		//$this->image_directory = 'userfiles/product/';
		//$this->thumb_directory = 'userfiles/product/thumb/';
		//$this->thumb_width = 125;
		//$this->thumb_height = 125;
		//if($_FILES['pk_image']['name'] != '' ) {
		//	$filename = $this->_upload_image('pk_image');
		//	$this->db->set('pk_image' , $filename);
		//}
	}



}
