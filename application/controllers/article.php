<?php

class Article extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
	}

	function index() {
		$this->view();
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

}
