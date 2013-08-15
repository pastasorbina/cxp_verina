<?php

class Credit extends MY_Controller {

	var $mod_title = '';
	var $use_cookies = FALSE;

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->session->validate_member();

		$this->sci->assign('mod_title' , $this->mod_title);
		$this->load->library('cart');

		$this->userinfo = $this->session->get_userinfo('member');

		//get last brand visited
		$last_brand_visited = $this->session->get_bread('brand-view');
		$this->sci->assign('last_brand_visited' , $last_brand_visited);

		$this->icart->debug();
	}

	function use_credit() {
		$this->sci->d('use_credit.htm');
	}


}
