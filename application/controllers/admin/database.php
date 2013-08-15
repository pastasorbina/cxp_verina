<?php
class Database extends MY_Controller {

	var $mod_title = 'Database Tools';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		//if($this->userinfo['b_id'] != 0) {
		//	show_error('unauthorized !', 401);
		//}

		$this->sci->assign('use_ajax' , TRUE);

	}

	function index() {
		$this->sci->da('index.htm');
	}

	function truncate() {
		$this->mod_subtitle = 'DB Truncater';

		//auto selected
		$selected = array();
		//$selected[] = 'content';
		//$selected[] = 'content_category';
		//$selected[] = 'content_comment';
		//$selected[] = 'content_history';
		//$selected[] = 'content_option';
		//$selected[] = 'content_tag';
		//$selected[] = 'form';
		//$selected[] = 'form_detail';
		//$selected[] = 'form_result';
		//$selected[] = 'gallery';
		//$selected[] = 'gallery_album';
		//$selected[] = 'event';
		//$selected[] = 'links';
		//$selected[] = 'media';
		//$selected[] = 'media_category';
		//$selected[] = 'media_relation';
		//$selected[] = 'menu';
		//$selected[] = 'product';
		//$selected[] = 'product_category';
		//$selected[] = 'product_option';
		//$selected[] = 'product_tag';
		//$selected[] = 'testimoni';
		//$selected[] = 'testimoni';
		$this->sci->assign('selected' , $selected);

		$list_tables = $this->db->list_tables();
		$a=0;
		foreach($list_tables as $k=>$tmp) {
			$tables[$a]['table_name']= $tmp;
			$num_of_results = $this->db->count_all_results($tmp);
			$tables[$a]['num_of_results']= $num_of_results;
			$a++;
		}
		$this->sci->assign('tables' , $tables);

		$this->load->library('form_validation');

		foreach($list_tables as $k=>$tmp) {
			$this->form_validation->set_rules($tmp, '', '');
		}

		if ($this->form_validation->run() == FALSE) {
			$this->sci->da('truncate.htm');
		} else {
			$post = $_POST;
			foreach($post as $k=>$tmp) {
				$query = "truncate table ".$k.";";
				$this->db->query($query);
			}
			$this->session->set_confirm(1);
			//$this->sci->da('truncate.htm');
			redirect($this->mod_url."truncate");
		}


	}


}
