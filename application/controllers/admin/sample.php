<?php
class Sample extends MY_Controller {

	//var $module = 'page';
	var $mod_title = 'Sample';
	var $available_position = array();
	var $option = array();

	var $table_name = 'content';
	var $id_field = 'c_id';
	var $status_field = 'c_status';
	var $entry_field = 'c_entry';
	var $stamp_field = 'c_stamp';
	var $deletion_field = 'c_deletion';
	var $order_field = 'c_publish_date';

	var $search_in = array('c_title','c_content_full','c_content_intro');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
	}

	function join_setting() {
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
	}



}
