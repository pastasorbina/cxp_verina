<?php

class Package extends MY_Controller {

	var $mod_title = '';

	var $table_name = 'package';
	var $id_field = 'pk_id';
	var $status_field = 'pk_status';
	var $entry_field = 'pk_entry';
	var $stamp_field = 'pk_stamp';
	var $deletion_field = 'pk_deletion';
	var $order_field = 'pk_date';

	var $search_in = array('pk_title', 'pk_desc');

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


		////get banner
		//$this->db->where('b_id' , $this->branch_id);
		//$this->db->where('bn_status' , 'Active');
		//$res = $this->db->get('banner');
		//$banner = $res->result_array();
		//$this->sci->assign('banner' , $banner);
		//
		////get news
		//$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		//$this->db->where('cl_code' , 'news');
		//$this->db->where('content.b_id' , $this->branch_id);
		//$this->db->where('c_status' , 'Active');
		//$this->db->order_by('c_date' , 'DESC');
		//$this->db->limit(4,0);
		//$res = $this->db->get('content');
		//$news = $res->result_array();
		//foreach($news as $k=>$tmp) {
		//	$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
		//	$this->db->where('mr_foreign_id' , $tmp['c_id']);
		//	$this->db->where('mr_module' , 'content');
		//	$res = $this->db->get('media_relation');
		//	$media = $res->result_array();
		//	foreach($media as $l=>$tmp) {
		//		$pos = $tmp['mr_pos'];
		//		$news[$k]['media'][$pos] = $tmp;
		//	}
		//}
		//$this->sci->assign('news' , $news);

		$this->sci->da('view.htm');
	}

	function category( $pkc_id=0, $offset=0, $encodedkey='' ) {
		$this->session->set_bread('list');

		$pagelimit = 0;

		//assign default filter params
		$this->sci->assign('pkc_id' , $pkc_id);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		//assign other filters
		$this->db->where('pkc_status' , 'Active');
		$this->db->where('pkc_id' , $pkc_id);
		$this->db->where('b_id' , $this->branch_id );
		$res = $this->db->get('package_category');
		$category = $res->row_array();
		$this->sci->assign('category' , $category);

		$this->db->where('pkc_status' , 'Active');
		$this->db->where('b_id' , $this->branch_id );
		$res = $this->db->get('package_category');
		$all_category = $res->result_array();
		$this->sci->assign('all_category' , $all_category);

		$this->db->start_cache();
		//$this->db->join('gallery_album' , 'condition' , 'left');
		$this->db->where('package.b_id' , $this->branch_id);
		$this->db->where('pkc_id' , $pkc_id);
		$this->db->where( 'pk_status' , 'Active' );
		$this->db->order_by('pk_entry' , 'DESC');
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
		$config['base_url'] = $this->mod_url."category/$pkc_id/";
		$config['suffix'] = "/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);

		//$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		//print $this->mod;
		foreach($maindata as $k=>$tmp) {
			$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
			$this->db->where('mr_foreign_id' , $maindata[$k]['pk_id']);
			$this->db->where('mr_module' , $this->mod);
			$res = $this->db->get('media_relation');
			$media = $res->result_array();
			//print_r($media);
			foreach($media as $l=>$tmp) {
				$pos = $tmp['mr_pos'];
				$maindata[$k]['media'][$pos] = $tmp;
			}
		}

		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		//$breadcrumb[] = "Paket";
		$breadcrumb[] = $category['pkc_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('category.htm');
	}


}
