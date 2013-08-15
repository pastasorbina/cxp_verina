<?php
class Gift_card_product extends MY_Controller {

	var $mod_title = 'Manage Gift Card Product';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'product';
	var $id_field = 'p_id';
	var $status_field = 'p_status';
	var $entry_field = 'p_entry';
	var $stamp_field = 'p_stamp';
	var $deletion_field = 'p_deletion';
	var $order_field = 'p_order';
	var $order_dir = 'ASC';

	var $author_field = 'p_author';
	var $editor_field = 'p_editor';

	var $search_in = array('p_name');

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


	function index($pagelimit=10, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->join_setting();
			$this->where_setting();
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('p_type' , 'Giftcard');
	}

	function validation_setting() {
		$this->form_validation->set_rules('p_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('p_price', 'Price', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_giftcard_nominal', 'Nominal', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_description', 'Description', 'trim');
		$this->form_validation->set_rules('p_order', 'Ordering', 'trim');
	}

	function database_setter() {
		$this->db->set('p_name' , $this->input->post('p_name') );
		$this->db->set('p_code' , $this->input->post('p_code') );
		$this->db->set('p_price' , $this->input->post('p_price') );
		$this->db->set('p_giftcard_nominal' , $this->input->post('p_giftcard_nominal') );
		$this->db->set('p_description' , $this->input->post('p_description') );
		$this->db->set('p_order' , $this->input->post('p_order') );
		$this->db->set('p_type' , 'Giftcard');

		if($_FILES['p_image1']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image1');
			$this->db->set('p_image1' , $filename);
		}
	}

	function _upload_product_image( $fieldname = '', $crop = FALSE ) {
		$l_dir = 'userfiles/product/l/';
		$m_dir = 'userfiles/product/m/';
		$s_dir = 'userfiles/product/s/';
		$m_width = 300; $m_height = 300;
		$s_width = 50; $s_height = 50;
		$filename = FALSE;
		if (!empty($_FILES)) {
			if ($_FILES[$fieldname]['tmp_name']) {
				$code = uniqid();
				$realFile = $_FILES[$fieldname]['name'];
				$extension = end(explode(".", $realFile));
				$tempFile = $_FILES[$fieldname]['tmp_name'];
				$filename = $code . '.' . $extension;
				$l_file =  $l_dir . $filename;
				$m_file =  $m_dir . $filename;
				$s_file =  $s_dir . $filename;
				move_uploaded_file($tempFile, $l_file);
				@chmod($l_file, 0755);

				// make medium
				$config['image_library'] = 'gd2';
				$config['source_image'] = $l_file;
				$config['new_image'] = $m_file;
				$config['width'] = $m_width;
				$config['height'] = $m_height;
				$config['maintain_ratio'] = FALSE;
				$this->load->library('image_lib');
				$this->image_lib->initialize($config);
				$this->image_lib->resize();
				$this->image_lib->clear();
				@chmod($m_file, 0755);

				// make small
				$config['image_library'] = 'gd2';
				$config['source_image'] = $l_file;
				$config['new_image'] = $s_file;
				$config['width'] = $s_width;
				$config['height'] = $s_height;
				$config['maintain_ratio'] = FALSE;
				$this->load->library('image_lib');
				$this->image_lib->initialize($config);
				$this->image_lib->resize();
				$this->image_lib->clear();
				@chmod($s_file, 0755);
			}
		}
		return $filename;
	}


	function pre_add_edit() {}

	function pre_add() {}

	function pre_edit($id=0) {}

	function give( $p_id=0 ){
		//get give card product
		$this->db->where('p_id' , $p_id);
		$this->db->where('p_type' , "Giftcard");
		$this->db->where('p_status' , 'Active');
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product);

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
					$this->db->set('gc_nominal' , $product['p_giftcard_nominal'] );
					$this->db->set('gc_name' , $product['p_name'] );
					$this->db->set('p_id' , $p_id );
					$this->db->set('m_id' , $tmp['m_id'] );
					$this->db->set('gc_is_used' , 'No' );
					$this->db->set('gc_entry' , 'NOW()', false );
					$this->db->set('gc_received_date' , 'NOW()', false );
					$this->db->set('gc_expire_date' , date_future('','+3months') );
					$this->db->set('gc_author' , $this->userinfo['u_id'] );

					//print "m_id ".$tmp['m_id']." ";
					//print "gcp ".$gcp_id." ";
					$next_code = $this->generate_code($p_id);
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
