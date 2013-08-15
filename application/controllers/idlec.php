<?php

class Idlec extends MY_Controller {


	function __construct() {
		parent::__construct();
	}

	function index(){
		show_404();
	}

	function get_server_date($returntype='datetime'){
		switch($returntype){
			case 'datetime' : $ret['datetime'] = date('Y-m-d H:i:s'); break;
			case 'array' :
				$date = date('Y-m-d-H-i-s');
				$date_arr = explode('-', $date);
				$ret['year'] = $date_arr[0];
				$ret['month'] = $date_arr[1];
				$ret['day'] = $date_arr[2];
				$ret['hour'] = $date_arr[3];
				$ret['minute'] = $date_arr[4];
				$ret['second'] = $date_arr[5];
		}
		echo json_encode($ret);
	}


}
