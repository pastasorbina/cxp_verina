<?php
class Transaction2 extends MY_Controller {

	var $mod_title = 'List of Transactions';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'transaction';
	var $id_field = 'trans_id';
	var $status_field = 'trans_status';
	var $entry_field = 'trans_entry';
	var $stamtrans_field = 'trans_stamp';
	var $deletion_field = 'trans_deletion';
	var $order_field = 'trans_entry';

	var $author_field = 'trans_author';
	var $editor_field = 'trans_editor';

	var $search_in = array('trans_id', 'm_firstname','m_lastname','m_email');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}

	function index($trans_payment_status='any', $pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		$this->sci->assign('trans_payment_status' , $trans_payment_status);
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
			if($trans_payment_status != 'any') {
				$this->db->where('trans_payment_status' , $trans_payment_status);
			}
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$trans_payment_status/". $pagelimit ."/";
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


	function enum_setting($maindata=array()) {

		return $maindata;
	}

	function join_setting() {
		$this->db->join('member m' , 'm.m_id = transaction.m_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('trans_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('trans_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('pc_id', 'Category', 'trim|xss_clean');
		$this->form_validation->set_rules('pt_id', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('br_id', 'Brand', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_order', 'Ordering', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_price', 'Price', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_discount_price', 'Discounted Price', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_weight', 'Weight', 'trim|xss_clean');
		$this->form_validation->set_rules('trans_description', 'Description', 'trim');
	}

	function database_setter() {
		$this->db->set('pc_id' , $this->input->post('pc_id'));
		$this->db->set('pt_id' , $this->input->post('pt_id'));
		$this->db->set('br_id' , $this->input->post('br_id'));
		$this->db->set('trans_order' , $this->input->post('trans_order'));
		$this->db->set('trans_name' , $this->input->post('trans_name') );
		$this->db->set('trans_code' , $this->input->post('trans_code') );
		$this->db->set('trans_price' , $this->input->post('trans_price') );
		$this->db->set('trans_discount_price' , $this->input->post('trans_discount_price') );
		$this->db->set('trans_description' , $this->input->post('trans_description') );
		$this->db->set('trans_weight' , $this->input->post('trans_weight') );

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['trans_image1']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image1');
			$this->db->set('trans_image1' , $filename);
		}
		if($_FILES['trans_image2']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image2');
			$this->db->set('trans_image2' , $filename);
		}
		if($_FILES['trans_image3']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image3');
			$this->db->set('trans_image3' , $filename);
		}
		if($_FILES['trans_image4']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image4');
			$this->db->set('trans_image4' , $filename);
		}
		if($_FILES['trans_image5']['name'] != '' ) {
			$filename = $this->_upload_image('trans_image5');
			$this->db->set('trans_image5' , $filename);
		}
	}


	function pre_add_edit() {

	}

	function pre_add() {
	}

	function pre_edit($id=0) {
		$this->db->where('pq_status' , 'Active');
		$this->db->where('trans_id' , $id);
		$res = $this->db->get('product_quantity');
		$product_quantity = $res->result_array();
		$this->sci->assign('product_quantity' , $product_quantity);
	}

	//function delete($id=0) {
	//	$this->change_status($id, 'Deleted');
	//}
	//
	//function add_quantity() {
	//	$trans_id = $this->input->post('trans_id');
	//	$pq_size = $this->input->post('pq_size');
	//	$pq_quantity = $this->input->post('pq_quantity');
	//	$userinfo = $this->session->get_userinfo();
	//
	//	$this->load->library('form_validation');
	//	$this->form_validation->set_rules('pq_size', 'Size', 'required|trim|xss_clean');
	//	$this->form_validation->set_rules('pq_quantity', 'Quantity', 'required|trim|xss_clean');
	//	$this->form_validation->set_rules('trans_id', 'trans_id', 'required|trim|xss_clean');
	//
	//	if($this->form_validation->run() == FALSE) {
	//	} else {
	//		$this->db->set('trans_id' , $trans_id);
	//		$this->db->set('pq_size' , $pq_size);
	//		$this->db->set('pq_quantity' , $pq_quantity);
	//		$this->db->set('pq_author' , $userinfo['u_id']);
	//		$this->db->set('pq_entry' , date('Y-m-d H:i:s') );
	//		$ok = $this->db->insert('product_quantity');
	//	}
	//	redirect($this->mod_url."edit/$trans_id");
	//}
	//
	//function update_quantity() {
	//	$trans_id = $this->input->post('trans_id');
	//	$pq_id = $this->input->post('pq_id');
	//	$pq_size = $this->input->post('pq_size');
	//	$pq_quantity = $this->input->post('pq_quantity');
	//	$userinfo = $this->session->get_userinfo();
	//
	//	$this->load->library('form_validation');
	//	$this->form_validation->set_rules('pq_id', 'pq_id', 'xss_clean');
	//	$this->form_validation->set_rules('pq_size', 'Size', 'xss_clean');
	//	$this->form_validation->set_rules('pq_quantity', 'Quantity', 'xss_clean');
	//	$this->form_validation->set_rules('trans_id', 'trans_id', 'required|xss_clean');
	//
	//	if($this->form_validation->run() == FALSE) {
	//		//print_r($pq_id);
	//		//exit();
	//	} else {
	//		//print_r($pq_id);
	//		//print_r($pq_quantity);
	//		//print_r($pq_size);
	//		foreach($pq_id as $k=>$tmp){
	//			$this->db->set('pq_quantity' , $pq_quantity[$k]);
	//			$this->db->set('pq_size' , $pq_size[$k]);
	//			$this->db->where('pq_id' , $tmp);
	//			$this->db->update('product_quantity');
	//		}
	//		//exit();
	//	}
	//	redirect($this->mod_url."edit/$trans_id");
	//}

	function view($trans_id=0) {
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();

		//get detail
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();

		//get confirmation
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_confirmation');
		$trans_confirmation = $res->row_array();

		$this->sci->assign('trans' , $trans);
		$this->sci->assign('trans_detail' , $trans_detail);
		$this->sci->assign('trans_confirmation' , $trans_confirmation);

		$this->sci->da('view.htm');
	}


	function change_to_paid($trans_id=0) {
		$this->db->where('trans_id' , $trans_id);
		$this->db->set('trans_payment_status' , 'Paid');
		$ok = $this->db->update('transaction');
		if($ok) {
			$this->session->set_confirm(1);
		} else {
			$this->session->set_confirm(0);
		}
		redirect( $this->session->get_bread('list') );
	}





}
