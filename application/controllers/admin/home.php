<?php

class Home extends MY_Controller {

	var $mod_title = 'Dashboard';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('admin');
		$this->_init();
		$this->session->validate(array(), 'admin');
	}

	function index() {
		//print "<br><br><br><br>";
		//$number = 000000001;
		//print "<hr>";
		//print generate_luhn($number);
		//print $number;
		
		$userinfo = $this->session->get_userinfo();
		//print_r($userinfo);
		$this->sci->da('index.htm');
	}

}
