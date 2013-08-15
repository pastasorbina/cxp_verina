<?php
class Gift_card_product extends MY_Controller {

	var $mod_title = 'Manage Gift Card Product';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'gift_card_product';
	var $id_field = 'gcp_id';
	var $status_field = 'gcp_status';
	var $entry_field = 'gcp_entry';
	var $stamgcp_field = 'gcp_stamp';
	var $deletion_field = 'gcp_deletion';
	var $order_field = 'gcp_entry';

	var $author_field = 'gcp_author';
	var $editor_field = 'gcp_editor';

	var $search_in = array('gcp_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->userinfo = $this->session->get_userinfo();
		$this->sci->assign('use_ajax' , FALSE);
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('gcp_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('gcp_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('gcp_nominal', 'Nominal', 'trim|xss_clean');
		$this->form_validation->set_rules('gcp_description', 'Description', 'trim');
	}

	function database_setter() {
		$this->db->set('gcp_name' , $this->input->post('gcp_name') );
		$this->db->set('gcp_code' , $this->input->post('gcp_code') );
		$this->db->set('gcp_nominal' , $this->input->post('gcp_nominal') );
		$this->db->set('gcp_description' , $this->input->post('gcp_description') );
	}

	function pre_add_edit() {}

	function pre_add() {}

	function pre_edit($id=0) {}

	function give( $gcp_id=0 ){
		//get give card product
		$this->db->where('gcp_id' , $gcp_id);
		$this->db->where('gcp_status' , 'Active');
		$res = $this->db->get('gift_card_product');
		$gift_card_product = $res->row_array();
		$this->sci->assign('gift_card_product' , $gift_card_product);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('to_all', '', 'trim|xss_clean');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('give.htm');
		} else {
			$to_all = $this->input->post('to_all');
			if($to_all == 'Yes') {
				//get all member that is active and has activate account
				$this->db->where('m_status' , 'Active');
				$this->db->where('m_is_active' , 'Yes');
				$res = $this->db->get('member');
				$all_member = $res->result_array();

				//create gift card for each member
				foreach($all_member as $k=>$tmp) {
					$this->db->set('gc_nominal' , $gift_card_product['gcp_nominal'] );
					$this->db->set('gc_name' , $gift_card_product['gcp_name'] );
					$this->db->set('gcp_id' , $gcp_id );
					$this->db->set('gcp_id' , $gcp_id );
					$this->db->set('m_id' , $tmp['m_id'] );
					$this->db->set('gc_is_used' , 'No' );
					$this->db->set('gc_entry' , 'NOW()', false );
					$this->db->set('gc_received_date' , 'NOW()', false );
					$this->db->set('gc_expire_date' , date_future('','+3months') );
					$this->db->set('gc_author' , $this->userinfo['u_id'] );

					//print "m_id ".$tmp['m_id']." ";
					//print "gcp ".$gcp_id." ";
					$next_code = $this->generate_code($gcp_id);
					//print "next [$next_code] ";
					//print " [".strlen($next_code)." digit]";
					//print "<hr>";

					$this->db->set('gc_code' , $next_code );
					$ok = $this->db->insert('gift_card');
					$insert_id = $this->db->insert_id();
				}
			}
			$this->session->set_confirm(1);
			redirect($this->session->get_bread('list'));
		}

	}

	//generate code batch by gift card product
	function generate_code($gcp_id=0) {
		$prefix = "G";
		$gcp_prefix = parse_str($gcp_id);
		$gcp_prefix = str_repeat("0", (3-strlen($gcp_id))). $gcp_id;
		$prefix = $prefix.$gcp_prefix;

		$digit = 9; //4gc+9code+2pin+1luhn

		// Get latest gift card
		$this->db->like('gc_code' , $prefix);
		$this->db->order_by('gc_code' , 'DESC');
		$this->db->limit(1);
		$res = $this->db->get('gift_card');
		$last_gift_card = $res->row_array();

		$pin = rand(10,99);

		if ( sizeof($last_gift_card) > 0) {
			$code = $last_gift_card['gc_code'];
			$current = substr($code , strlen($prefix) , (strlen($code)-strlen($prefix) - 3 /*-luhn+pin digit*/ ));
			$next = $current +1;
			$next = str_repeat("0" , ( $digit - strlen($next) ) ) . $next;
			$next_and_pin = $next.$pin;
			$luhn = generate_luhn($next_and_pin, FALSE);
			$next_code = $prefix.$next_and_pin.$luhn;
			//print "-$code-$next-$next_code ";
		} else {
			$next = str_repeat("0" , ($digit));
			$next_and_pin = $next.$pin;
			$luhn = generate_luhn($next_and_pin, FALSE);
			$next_code = $prefix.$next_and_pin.$luhn;
		}
		return $next_code;

	}






}
