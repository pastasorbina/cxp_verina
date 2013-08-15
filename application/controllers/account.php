<?php

class Account extends MY_Controller {

	var $mod_title = 'MY ACCOUNT';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		//$this->session->validate(array(), 'main', TRUE);
		$this->sci->assign('mod_title' , $this->mod_title);
	}

	function _load_topbar(){
		$html = $this->sci->fetch('account/topbar.htm');
		$this->sci->assign('account_topbar' , $html);
	}

	function _load_sidebar(){
		$html = $this->sci->fetch('account/sidebar.htm');
		$this->sci->assign('account_sidebar' , $html);
	}

	function index() {
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "My Account";
		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->_load_topbar();
		$this->_load_sidebar();
		$this->sci->da('index.htm');
	}

	function view($br_id=0, $pagelimit=9, $offset=0) {
		$this->session->set_bread('list');
		$this->sci->da('view.htm');
	}

	function view_voucher() {
		//$this->sci->da('main/default/development.htm', TRUE);
		$this->sci->in_development();
	}



}
