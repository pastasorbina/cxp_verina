<?php

class Group_news extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
	}
	
	function index($offset=0, $orderby='c_date', $ascdesc='DESC', $encodedkey='') {
		if($this->branch_id != 1) { show_404();  }
		
		//$pagelimit =3;  
		//$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left'); 
		//$this->db->where('cl_code' , 'News');
		//$this->db->where('c_status' , 'Active'); 
		//$this->db->order_by('c_date' , 'DESC');  
		//$this->db->limit($pagelimit, 0);
		//$res = $this->db->get('content'); 
		//$maindata = $res->result_array();
		//foreach($maindata as $k=>$tmp) {
		//	$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
		//	$this->db->where('mr_foreign_id' , $maindata[$k]['c_id']);
		//	$this->db->where('mr_module' , 'content');
		//	$res = $this->db->get('media_relation');
		//	$media = $res->result_array();
		//	foreach($media as $l=>$tmp) {
		//		$pos = $tmp['mr_pos'];
		//		$maindata[$k]['media'][$pos] = $tmp;
		//	}
		//	
		//	$this->db->where('b_id' , $tmp['b_id']);
		//	$res = $this->db->get('branch');
		//	$branch = $res->row_array();
		//	$branch_code = $branch	['b_code'];
		//	if($branch_code == 'alsut') { $branch_code = 'alamsutera'; }
		//	$maindata[$k]['branch'] = $branch_code;
		//} 
		//$this->sci->assign('maindata' , $maindata);
		
		
		$pagelimit = 15;

		$this->db->start_cache();
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left'); 
		$this->db->where('cl_code' , 'News');
		$this->db->where('c_status' , 'Active'); 
		$this->db->order_by('c_date' , 'DESC'); 
		$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
		$this->db->order_by($orderbyconv , $ascdesc);
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			foreach($this->search_in as $k=>$tmp) {
				$this->db->or_like($tmp, $searchkey);
			}
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->stop_cache();


		$total = $this->db->count_all_results('content');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 3;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('content');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
			$this->db->where('mr_foreign_id' , $maindata[$k]['c_id']);
			$this->db->where('mr_module' , 'content');
			$res = $this->db->get('media_relation');
			$media = $res->result_array();
			foreach($media as $l=>$tmp) {
				$pos = $tmp['mr_pos'];
				$maindata[$k]['media'][$pos] = $tmp;
			}
			
			$this->db->where('b_id' , $tmp['b_id']);
			$res = $this->db->get('branch');
			$branch = $res->row_array();
			$branch_code = $branch['b_code'];
			$branch_name = $branch['b_name'];
			if($branch_code == 'alsut') { $branch_code = 'alamsutera'; }
			$maindata[$k]['branch'] = $branch_code;
			$maindata[$k]['b_name'] = $branch_name;
		} 
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "News";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		
		$this->sci->da('index.htm');
	}

	 
}
