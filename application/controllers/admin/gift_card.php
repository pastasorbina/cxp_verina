<?php
class Gift_card extends MY_Controller {

	var $mod_title = 'Manage Gift Card';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'gift_card';
	var $id_field = 'gc_id';
	var $status_field = 'gc_status';
	var $entry_field = 'gc_entry';
	var $stamgc_field = 'gc_stamp';
	var $deletion_field = 'gc_deletion';
	var $order_field = 'gc_entry';

	var $author_field = 'gc_author';
	var $editor_field = 'gc_editor';

	var $search_in = array('gc_code');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('GIFTCARD_MANAGE'), 'admin');
		$this->userinfo = $this->session->get_userinfo();
		$this->sci->assign('use_ajax' , FALSE);
		
		$this->load->model('mod_gift_card');
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('member' , 'member.m_id = gift_card.m_id' , 'left');
		$this->db->join('product' , 'product.p_id = gift_card.p_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('gc_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('gc_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('gc_nominal', 'Nominal', 'trim|xss_clean');
		$this->form_validation->set_rules('gc_description', 'Description', 'trim');
	}

	function database_setter() {
		$this->db->set('gc_name' , $this->input->post('gc_name') );
		$this->db->set('gc_code' , $this->input->post('gc_code') );
		$this->db->set('gc_nominal' , $this->input->post('gc_nominal') );
		$this->db->set('gc_description' , $this->input->post('gc_description') );
	}

	function index($p_id=0, $pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {

		$this->session->set_bread('list');

		$this->db->where('p_status' , 'Active');
		$this->db->where('p_type' , 'Giftcard');
		$res = $this->db->get('product');
		$all_gift_card_product = $res->result_array();
		$this->sci->assign('all_gift_card_product' , $all_gift_card_product);

		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		$this->sci->assign('p_id' , $p_id);
		/*--cache-start--*/
		$this->db->start_cache();
			if($p_id !=0 ) { $this->db->where('gift_card.p_id' , $p_id); }
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
		$config['base_url'] = $this->mod_url."index/".$p_id."/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
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

	function pre_add_edit() { }

	function pre_add() { }

	function pre_edit($id=0) { }


	function list_assigned_gift_card($p_id=0, $pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {

		$this->session->set_bread('list');
		//get product
		$this->db->where('p_id' , $p_id);
		$this->db->where('p_status' , "Active");
		$this->db->where('p_type' , 'Giftcard');
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product);

		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		$this->sci->assign('p_id' , $p_id);

		$search_in = array(
		);

		/*--cache-start--*/
		$this->db->start_cache();
			if($p_id !=0 ) { $this->db->where('gift_card.p_id' , $p_id); }
			if($encodedkey != ''){
				$searchkey = safe_base64_decode($encodedkey);
				if(!empty($search_in)) {
					foreach($search_in as $k=>$tmp) { $this->db->or_like($tmp, $searchkey); }
				}
				$this->sci->assign('searchkey' , $searchkey);
			}
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->db->join('member' , 'member.m_id = gift_card.m_id' , 'left');
			$this->db->join('product' , 'product.p_id = gift_card.p_id' , 'left');
			$this->db->where($this->status_field ,"Active");
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results("gift_card");
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."list_assigned_gift_card/".$p_id."/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
		$this->pagination->initialize($config); 
		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get("gift_card");
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('list_assigned_gift_card.htm');
	}


	function gift_card_filter() {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}
	
	
	function cancel_gift_card($gc_id=0) {
		$this->db->where('gc_id' , $gc_id);
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array();
		
		if(!$gift_card) {
			$this->session->set_confirm(0,'Error, Gift Card Not Found !!');
		} else {
			if($gift_card['gc_is_used'] == 'Yes') {
				$this->session->set_confirm(0,'You Cannot Cancel a Used Gift Card !');
			} else {
				$this->db->where('gc_id' , $gc_id);
				$this->db->set('gc_status' , 'Deleted');
				$this->db->update('gift_card');
				$this->session->set_confirm(1, 'Gift Card Cancelled' );
			}
		}
		
		redirect($this->session->get_bread('list') );
	}

	function list_gift_card_item($pagelimit=10, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
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
		$search_in = array(
			'p_name',
		);

		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){
				$searchkey = safe_base64_decode($encodedkey);
				if(!empty($search_in)) {
					foreach($search_in as $k=>$tmp) { $this->db->or_like($tmp, $searchkey); }
				} else {
					$this->search_setting($searchkey);
				}
				$this->sci->assign('searchkey' , $searchkey);
			}
			//$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			//$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->where('p_status' , 'Active');
			$this->db->where('p_type' , 'Giftcard');
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('product');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."list_gift_card_item/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('product');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		//$maindata = $this->append_user($maindata);
		//$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('list_gift_card_item.htm');
	}

	function gift_card_item_rules($action='add') {
		$this->form_validation->set_rules('p_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('p_price', 'Price', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_giftcard_nominal', 'Nominal', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_description', 'Description', 'trim');
		$this->form_validation->set_rules('p_order', 'Ordering', 'trim');
		$this->form_validation->set_rules('p_is_published', 'Is Published', 'trim');
	}

	function gift_card_item_database_setting($action='add'){
		$this->db->set('p_name' , $this->input->post('p_name') );
		$this->db->set('p_code' , $this->input->post('p_code') );
		$this->db->set('p_price' , $this->input->post('p_price') );
		$this->db->set('p_giftcard_nominal' , $this->input->post('p_giftcard_nominal') );
		$this->db->set('p_description' , $this->input->post('p_description') );
		$this->db->set('p_order' , $this->input->post('p_order') );
		$this->db->set('p_is_published' , $this->input->post('p_is_published') );
		$this->db->set('p_type' , 'Giftcard');

		if($_FILES['p_image1']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image1');
			$this->db->set('p_image1' , $filename);
		}
		if($action == 'add') {
			$this->db->set('p_entry' , 'NOW()', FALSE);
			$this->db->set('p_author' , $this->userinfo['u_id']);
		}
	}

	function add_gift_card_item(){
		$this->sci->assign('ajax_action' , 'add');
		$this->load->library('form_validation');
		$this->gift_card_item_rules('add');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('edit_gift_card_item.htm');
		} else {
			$this->gift_card_item_database_setting('add');
			$this->db->insert('product');
			$insert_id = $this->db->insert_id();
			$this->session->set_confirm(1);
			redirect($this->session->get_bread('list'));
		}
	}

	function edit_gift_card_item($p_id=0){
		$this->sci->assign('ajax_action' , 'edit');
		$this->db->where('p_id' , $p_id);
		$this->db->where('p_status' , 'Active');
		$this->db->where('p_type' , 'Giftcard');
		$res = $this->db->get('product');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->gift_card_item_rules('edit');
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('edit_gift_card_item.htm');
		} else {
			$this->db->where('p_id' , $p_id);
			$this->gift_card_item_database_setting('edit');
			$this->db->update('product');
			$this->session->set_confirm(1);
			redirect($this->session->get_bread('list'));
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
 
	
	function send_gift_card_form($p_id=0, $offset=0, $encodedkey='') {
		$this->sci->assign('p_id' , $p_id);

		$this->db->where('p_id' , $p_id);
		$this->db->where('p_type' , "Giftcard");
		$this->db->where('p_status' , 'Active');
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product); 

		$this->load->library('form_validation');
		$this->form_validation->set_rules('m_id[]', 'Member', 'trim|xss_clean|required');
		$this->form_validation->set_rules('gc_remark', 'Remark', 'trim|xss_clean');
		$this->form_validation->set_rules('gc_shareable', 'Shareable', 'trim|xss_clean');
		if($this->form_validation->run() == FALSE) {

			$pagelimit=30;
			$this->sci->assign('pagelimit' , $pagelimit);
			$this->sci->assign('offset' , $offset);
			$this->db->start_cache();
				if($encodedkey != ''){
					$searchkey = safe_base64_decode($encodedkey);
					$search_in = array('m_firstname','m_lastname','m_login');
					foreach($search_in as $k=>$tmp) { $this->db->or_like($tmp, $searchkey); }
					$this->sci->assign('searchkey' , $searchkey);
				}
				$this->db->where('m_status' , 'Active');
			$this->db->stop_cache();
			$total = $this->db->count_all_results('member');
			$this->load->library('pagination');
			$config['base_url'] = $this->mod_url."send_gift_card_form/$p_id";
			$config['suffix'] = "/$encodedkey" ;
			$config['total_rows'] = $total;
			$config['per_page'] = $pagelimit;
			$config['uri_segment'] = 5;
			$this->pagination->initialize($config);
			$this->db->limit($pagelimit, $offset);
			$res = $this->db->get('member');
			$this->db->flush_cache();
			$members = $res->result_array();
			$this->sci->assign('members' , $members);
			$this->sci->assign('paging', $this->pagination->create_links() );

			$this->sci->da('send_gift_card_form.htm');
		} else {
			$list_m_id = $this->input->post('m_id');

			//get all member that is active and has activate account
			$this->db->where_in('m_id' , $list_m_id);
			$this->db->where('m_status' , 'Active');
			$this->db->where('m_is_active' , 'Yes');
			$res = $this->db->get('member');
			$all_member = $res->result_array();

			//create gift card for each member
			foreach($all_member as $k=>$tmp) {
				$this->db->set('gc_shareable' , $this->input->post('gc_shareable') );
				$this->db->set('gc_remark' , $this->input->post('gc_remark') );
				$this->db->set('gc_nominal' , $product['p_giftcard_nominal'] );
				$this->db->set('gc_name' , $product['p_name'] );
				$this->db->set('p_id' , $p_id );
				$this->db->set('m_id' , $tmp['m_id'] );
				$this->db->set('gc_is_used' , 'No' );
				$this->db->set('gc_entry' , 'NOW()', false );
				$this->db->set('gc_received_date' , 'NOW()', false );
				$this->db->set('gc_expire_date' , date_future('','+3months') );
				$this->db->set('gc_author' , $this->userinfo['u_id'] );

				$next_code = $this->generate_code($p_id);

				$this->db->set('gc_code' , $next_code );
				$ok = $this->db->insert('gift_card');
				$insert_id = $this->db->insert_id();
				
				//send email
				$this->mod_gift_card->send_email_to_receiver($insert_id);
			}
			$this->session->set_confirm(1, 'Giftcard is successfully sent !');
			redirect($this->session->get_bread('list'));
		}
	}


	function member_filter() {
		$searchkey = $this->input->post('searchkey');
		$searchkey = safe_base64_encode($searchkey);
		$page = $this->input->post('page');
		$href = $page."/0/$searchkey";
		redirect($href);
	}
	
	
	
	
	function edit_gift_card($gc_id=0){
		$this->sci->assign('ajax_action' , 'edit');
		
		$this->db->join('member m' , 'm.m_id = gift_card.m_id' , 'left');
		$this->db->where('gc_id' , $gc_id);
		$this->db->where('gc_status' , 'Active'); 
		$res = $this->db->get('gift_card');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('gc_name', 'Giftcard Name', 'trim|xss_clean');
		$this->form_validation->set_rules('gc_nominal', 'Nominal', 'trim|required|xss_clean');
		$this->form_validation->set_rules('gc_expire_date', 'Expired Date', 'trim|required|xss_clean');
		$this->form_validation->set_rules('gc_remark', 'Remark', 'trim|xss_clean');
		$this->form_validation->set_rules('gc_shareable', 'Shareable', 'trim|required|xss_clean');
		
		if($this->form_validation->run() == FALSE) {
			$this->sci->da('edit_gift_card.htm');
		} else {
			$this->db->where('gc_id' , $gc_id);
			$this->db->set('gc_name' , $this->input->post('gc_name') );
			$this->db->set('gc_nominal' , $this->input->post('gc_nominal') );
			$this->db->set('gc_expire_date' , $this->input->post('gc_expire_date') );
			$this->db->set('gc_remark' , $this->input->post('gc_remark') );
			$this->db->set('gc_shareable' , $this->input->post('gc_shareable') );
			$this->db->update('gift_card');
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
