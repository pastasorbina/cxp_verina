<?php
class Report_polling extends MY_Controller {

	var $mod_title = 'Polling Report';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('REPORT_VIEW'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->load_topbar();
	}

	function load_topbar() {
		$topbar = $this->sci->fetch('admin/report_polling/topbar.htm');
		$this->sci->assign('topbar' , $topbar);
	}

	function index(){
		$this->total_result();
	}

	function total_result() {

		//get polling
		$query = "SELECT * FROM member WHERE m_status = 'Active' GROUP BY m_poll ";
		$res = $this->db->query($query);
		$poll = $res->result_array();


		foreach($poll as $k=>$tmp) {
			$query = "SELECT * FROM member WHERE m_status = 'Active' AND m_poll = '".$tmp['m_poll']."' ";
			$res = $this->db->query($query);
			$count = $res->num_rows();
			$poll[$k]['count'] = $count;
			//print $count;
		}

		$this->sci->assign('poll' , $poll);


		$this->sci->da('index.htm');
	}




}
