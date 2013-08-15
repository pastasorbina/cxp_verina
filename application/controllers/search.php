<?php

class Search extends MY_Controller {

	var $mod_title = '';


	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->session->validate_member(FALSE);
	}

	function do_search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		//$searchby = $this->input->post('searchby');
		//$pagelimit = $this->input->post('pagelimit');
		//$orderby = $this->input->post('orderby');
		//$offset = $this->input->post('offset');
		//$ascdesc = $this->input->post('ascdesc');
		$searchkey = safe_base64_encode($searchkey);
		if( !$searchkey ) { $searchkey = ''; }
		redirect($this->mod_url."index/0/c_date/DESC/$searchkey");
	}

	function index($offset=0, $orderby='c_date', $ascdesc='DESC', $encodedkey='') {
		//if($this->branch_id != 1) { show_404();  }

		$pagelimit = 15;

		$this->search_in = array('c_title','c_content_full','c_content_intro');

		$this->db->start_cache();
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		if($this->branch_id != 1) {
			$this->db->where('content.b_id' , $this->branch_id);
		}
		//$this->db->where('cl_code' , 'News');
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

			$branch_code = "";
			$branch_name = $branch['b_name'];
			if($this->branch_id == 1) {
				$branch_code = $branch['b_code']."/";
				$branch_name = $branch['b_name'];
				if($branch_code == 'alsut/') { $branch_code = 'alamsutera/'; }
			}
			$maindata[$k]['branch'] = $branch_code;
			$maindata[$k]['b_name'] = $branch_name;
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Search";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('index.htm');
	}


}
