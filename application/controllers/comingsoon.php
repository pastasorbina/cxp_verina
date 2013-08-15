<?php

class Comingsoon extends MY_Controller {

	var $mod_title = '';
	var $cl_code='article';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->sci->assign('mod_title' , $this->mod_title);
		$this->sci->set_postlaunch(FALSE);
	}

	function index() {
		$this->sci->d('index.htm');
	}

}
