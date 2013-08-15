<?php

class Gallery extends MY_Controller {

	var $mod_title = '';

	var $table_name = 'gallery';
	var $id_field = 'g_id';
	var $status_field = 'g_status';
	var $entry_field = 'g_entry';
	var $stamp_field = 'g_stamp';
	var $deletion_field = 'g_deletion';
	var $order_field = 'g_date';

	var $search_in = array('g_title', 'g_desc');

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
	}

	function index() {
		$this->view_list();
	}



	function view($c_id=0) {

		$this->db->where('c_id' , $c_id);
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$res = $this->db->get('content');
		$content = $res->row_array();

		if(!$content) { show_404(); }

 

		$this->sci->da('view.htm');
	}

	function view_list( $ga_id=0, $offset=0, $encodedkey='' ) {
		$this->session->set_bread('list');
		
		$this->_load_sidebar();

		$pagelimit = 20;
		
		if($ga_id == 0) {
			$this->db->where('ga_status' , 'Active');
			$res = $this->db->get('gallery_album');
			$temp_gallery  = $res->row_array();
			$ga_id = $temp_gallery['ga_id'];
		}

		//assign default filter params
		$this->sci->assign('ga_id' , $ga_id);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		//assign other filters
		$this->db->where('ga_status' , 'Active');
		$this->db->where('b_id' , $this->branch_id );
		$res = $this->db->get('gallery_album');
		$gallery_album = $res->result_array();
		$this->sci->assign('gallery_album' , $gallery_album);
		
		$this->db->where('ga_id' , $ga_id);
		$res = $this->db->get('gallery_album');
		$this_gallery  = $res->row_array();

		$this->db->start_cache();
		//$this->db->join('gallery_album' , 'gallery_album.ga_id = gallery.ga_id' , 'left');
		$this->db->where('gallery.b_id' , $this->branch_id);
		$this->db->where('ga_id' , $ga_id);
		$this->db->where( 'g_status' , 'Active' );
		$this->db->order_by('g_date' , 'DESC');
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
		$config['base_url'] = $this->mod_url."view_list/$ga_id/";
		$config['suffix'] = "/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
			$this->db->where('mr_foreign_id' , $maindata[$k]['g_id']);
			$this->db->where('mr_module' , 'gallery');
			$res = $this->db->get('media_relation');
			$media = $res->result_array();
			foreach($media as $l=>$tmp) {
				$pos = $tmp['mr_pos'];
				$maindata[$k]['media'][$pos] = $tmp;
			}
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		//print_r($gallery_album);
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Gallery";
		$breadcrumb[] = $this_gallery['ga_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('list.htm');
	}


}
