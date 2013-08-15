<?php
class Report_transaction extends MY_Controller {

	var $mod_title = 'Transaction Report';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->load_topbar();
	}

	function load_topbar() {
		$topbar = $this->sci->fetch('admin/report_transaction/topbar.htm');
		$this->sci->assign('topbar' , $topbar);
	}

	function index(){
		$this->monthly_graph();
	}

	function total_result() {

		$this->sci->da('index.htm');
	}

	function monthly_graph($start_year=0, $start_month=0, $end_year=0, $end_month=0) {
		$this->sci->assign('start_year' , $start_year);
		$this->sci->assign('start_month' , $start_month);
		$this->sci->assign('end_year' , $end_year);
		$this->sci->assign('end_month' , $end_month);

		$month = date_make_selection_month();
		$this->sci->assign('month' , $month);
		$year = date_make_selection_year();
		$this->sci->assign('year' , $year);

		if($start_month != 0 AND $start_year!=0 AND $end_month!=0 AND $end_year!=0) {
			$start = "$start_year-$start_month-01";
			$end = "$end_year-$end_month-01";

			if($start <= $end) {
				$step = array();

				$d_start    = new DateTime($start);
				$d_end      = new DateTime($end);
				$interval = $d_start->diff($d_end);
				$year_diff = $interval->format('%y');
				$month_diff = $interval->format('%m');
				$diff_month = ($year_diff*12)+$month_diff;

				$a=0;
				$date = $start;
				for($i=0; $i<=$diff_month; $i++) {
					$step[$i]['year'] = date ("Y", strtotime($date));
					$step[$i]['month'] = date ("m", strtotime($date));
					$date = date ("Y-m-d", strtotime("+1 month", strtotime($date)));
				}

				$result = $step;

				foreach($step as $k=>$tmp) {
					$year = $tmp['year'];
					$month = $tmp['month'];
					$query = "SELECT *, MONTH(trans_entry) as date_month, YEAR(trans_entry) as date_year FROM transaction WHERE trans_status = 'Active'
					AND YEAR(trans_entry) = $year AND MONTH(trans_entry) = $month
					";
					$res = $this->db->query($query);
					$count = $res->num_rows();
					$result[$k]['count'] = $count;
					$result[$k]['month_string'] = date_string_month($month, TRUE);
					$result[$k]['year_string'] = $year;
				}

				$this->sci->assign('result' , $result);
			}

		}

		$this->sci->da('monthly.htm');
	}

	function monthly_change_filter() {
		$start_year = $this->input->post('start_year');
		$start_month = $this->input->post('start_month');
		$end_year = $this->input->post('end_year');
		$end_month = $this->input->post('end_month');
		redirect($this->mod_url."monthly_graph/$start_year/$start_month/$end_year/$end_month");
	}




}
