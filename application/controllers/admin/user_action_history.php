<?php

class User_action_history extends MY_Controller {

	var $mod_title = 'User Action History';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('USER'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

		$this->load->model('mod_global');

	}

	function index($type = 'none' , $action_sel = 'all' , $fy = '' , $fm = '' , $fd = '' , $uy = '' , $um = '' , $ud = '') {

		// Define Conditions
		if ($type == 'date') {
			$from = "$fy-$fm-$fd 0:0:0";
			$until = "$uy-$um-$ud 23:59:59";
			$this->sci->assign('from' , $from);
			$this->sci->assign('until' , $until);
			$this->sci->assign("fromuntil" , "From : $from - Until : $until");
			$where = "uah.uah_time >= '$from' AND uah.uah_time <= '$until'";
		}
		elseif ($type == 'all') {
			$this->sci->assign("fromuntil" , "All data");
			$where = "1 = 1";
		}
		else {
			$this->sci->assign("fromuntil" , "Please select date first");
			$where = "1 = 0";
		}

		// Action
		if ($action_sel != 'all') {
			$where .= " AND ua.ua_id = $action_sel";
		}

		// Define action
		$action = array(
			"all" => "All"
		);
		$res = $this->db->
			get('user_action');
		foreach ($res->result() as $v) {
			$action[$v->ua_id] = $v->ua_name;
		}
		$this->sci->assign('action' , $action);
		$this->sci->assign('action_sel' , $action_sel);

		$sql = "
			SELECT *
			FROM user_action_history uah
			LEFT JOIN user u ON u.u_id = uah.u_id
			LEFT JOIN user_action ua ON ua.ua_id = uah.ua_id
			WHERE $where
			ORDER BY uah.uah_time
		";

		$res = $this->db->query($sql);
		$this->sci->assign('action_history' , $res->result_array());

		$this->sci->da('index.htm');
	}

	function condition_change() {
		$type = $this->input->post('type');
		$action = $this->input->post('action');
		$page = $this->input->post('page');
		$fromYear = $this->input->post('fromYear');
		$fromMonth = $this->input->post('fromMonth');
		$fromDay = $this->input->post('fromDay');
		$untilYear = $this->input->post('untilYear');
		$untilMonth = $this->input->post('untilMonth');
		$untilDay = $this->input->post('untilDay');

		switch ($type) {
			case 'Last Month' :
				$thisYear = date('Y');
				$lastMonth = date('m') - 1;
				if ($lastMonth < 1) {
					$lastMonth = 12;
					$thisYear -= 1;
				}
				$lastDayOfMonth = date('t' , mktime (0,0,0,$lastMonth , 1, $thisYear));
				redirect("$page/date/$action/$thisYear/$lastMonth/1/$thisYear/$lastMonth/$lastDayOfMonth/");
			break;
			case 'Today' :
				$thisYear = date('Y');
				$thisMonth = date('m');
				$thisDay = date('d');
				redirect("$page/date/$action/$thisYear/$thisMonth/$thisDay/$thisYear/$thisMonth/$thisDay/");
			break;
			case 'This Month' :
				$thisYear = date('Y');
				$thisMonth = date('m');
				$lastDayOfMonth = date('t');
				redirect("$page/date/$action/$thisYear/$thisMonth/1/$thisYear/$thisMonth/$lastDayOfMonth/");
			break;
			case 'This Year' :
				$thisYear = date('Y');
				redirect("$page/date/$action/$thisYear/1/1/$thisYear/12/31/");
			break;
			case 'Filter by Date' :
				redirect("$page/date/$action/$fromYear/$fromMonth/$fromDay/$untilYear/$untilMonth/$untilDay/");
			break;
			case 'Show All' :
			default :
				redirect("$page/all/$action/");
		}
	}

}
?>
