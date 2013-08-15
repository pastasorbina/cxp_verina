<?php

class Speakers extends MY_Controller {

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
		$this->db->where('spc_status' , 'Active');
		$res = $this->db->get('speaker_category');
		$speaker_category = $res->result_array();
		$this->sci->assign('speaker_category' , $speaker_category);

		$this->db->join('speaker_category' , 'speaker_category.spc_id = speaker.spc_id' , 'left');
		$this->db->where('sp_status' , 'Active');
		$res = $this->db->get('speaker');
		$speaker = $res->result_array();
		$this->sci->assign('speaker' , $speaker);

		$this->sci->da('index.htm');
	}



	function view($sp_id=0) {

		$this->db->join('speaker_category' , 'speaker_category.spc_id = speaker.spc_id' , 'left');
		$this->db->where('sp_status' , 'Active');
		$this->db->where('sp_id' , $sp_id);
		$res = $this->db->get('speaker');
		$speaker = $res->row_array();

		if(!$speaker) { show_404(); }
		$this->sci->assign('speaker' , $speaker);

		$this->db->join('speaker_category' , 'speaker_category.spc_id = speaker.spc_id' , 'left');
		$this->db->where('sp_status' , 'Active');
		$this->db->where('speaker.spc_id' , $speaker['spc_id']);
		$this->db->where('sp_id !=' , $sp_id);
		$this->db->limit(3);
		$res = $this->db->get('speaker');
		$other = $res->result_array();
		$this->sci->assign('other' , $other);

		$this->sci->da('view.htm');
	}

	function view_list( $ga_id=0, $offset=0, $encodedkey='' ) {
		//$this->session->set_bread('list');
		//
		//$pagelimit = 20;
		//
		////assign default filter params
		//$this->sci->assign('ga_id' , $ga_id);
		//$this->sci->assign('pagelimit' , $pagelimit);
		//$this->sci->assign('offset' , $offset);
		////assign other filters
		//$this->db->where('ga_status' , 'Active');
		//$this->db->where('b_id' , $this->branch_id );
		//$res = $this->db->get('gallery_album');
		//$gallery_album = $res->result_array();
		//$this->sci->assign('gallery_album' , $gallery_album);
		//
		//$this->db->start_cache();
		////$this->db->join('gallery_album' , 'condition' , 'left');
		//$this->db->where('gallery.b_id' , $this->branch_id);
		//$this->db->where('ga_id' , $ga_id);
		//$this->db->where( 'g_status' , 'Active' );
		//$this->db->order_by('g_date' , 'DESC');
		//if($encodedkey != ''){
		//	$searchkey = safe_base64_decode($encodedkey);
		//	foreach($this->search_in as $k=>$tmp) {
		//		$this->db->or_like($tmp, $searchkey);
		//	}
		//	$this->sci->assign('searchkey' , $searchkey);
		//}
		//$this->db->stop_cache();
		//
		//$total = $this->db->count_all_results($this->table_name);
		//$this->load->library('pagination');
		//$config['base_url'] = $this->mod_url."view_list/$ga_id/";
		//$config['suffix'] = "/$encodedkey" ;
		//$config['total_rows'] = $total;
		//$config['per_page'] = $pagelimit;
		//$config['uri_segment'] = 4;
		//$this->pagination->initialize($config);
		//
		//$this->db->limit($pagelimit, $offset);
		//$res = $this->db->get($this->table_name);
		//$this->db->flush_cache();
		//$maindata = $res->result_array();
		//foreach($maindata as $k=>$tmp) {
		//	$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
		//	$this->db->where('mr_foreign_id' , $maindata[$k]['g_id']);
		//	$this->db->where('mr_module' , 'gallery');
		//	$res = $this->db->get('media_relation');
		//	$media = $res->result_array();
		//	foreach($media as $l=>$tmp) {
		//		$pos = $tmp['mr_pos'];
		//		$maindata[$k]['media'][$pos] = $tmp;
		//	}
		//}
		//$this->sci->assign('maindata' , $maindata);
		//$this->sci->assign('paging', $this->pagination->create_links() );
		//
		////assign breadcrumb
		//$breadcrumb = array();
		//$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		//$breadcrumb[] = "Gallery";
		//$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('index.htm');
	}


}
