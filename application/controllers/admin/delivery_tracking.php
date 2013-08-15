<?php
class Delivery_tracking extends MY_Controller {

	var $mod_title = 'Delivery Tracking Number';

	var $table_name = 'transaction';
	var $id_field = 'trans_id';
	var $status_field = 'trans_status';
	var $entry_field = 'trans_entry';
	var $stamtrans_field = 'trans_stamp';
	var $deletion_field = 'trans_deletion';
	var $order_field = 'trans_entry';

	var $author_field = 'trans_author';
	var $editor_field = 'trans_editor';

	var $search_in = array('trans_id');

	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('TRANSACTION_DELIVERY_TRACKING'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

	}

	function index($trans_payment_status='any', $pagelimit=100, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
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
		$maindata = $this->iteration_setting($maindata);
		//print_r($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function iteration_setting($maindata) {
		foreach($maindata as $k=>$tmp) {
			$this->db->where('trans_id' , $tmp['trans_id']);
			$this->db->where('transc_status' , 'Active');
			$res = $this->db->get('transaction_confirmation');
			$confirmation = $res->row_array();
			$maindata[$k]['confirmation'] = $confirmation;
		}
		return $maindata;
	}

	function join_setting() {
		$this->db->join('member m' , 'm.m_id = transaction.m_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('trans_payment_status' , 'Delivered');
			$this->db->where('trans_tn_entered' , "No");
	}

	function validation_setting() {
		$this->form_validation->set_rules('pollo_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pollc_id', 'Category', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pollo_order', 'Order', 'trim|xss_clean');
	}

	function database_setter() {
		$pollo_name = $this->input->post('pollo_name');
		$this->db->set('pollo_name' , $pollo_name );
		$this->db->set('pollc_id' , $this->input->post('pollc_id') );
		$this->db->set('pollo_order' , $this->input->post('pollo_order') );
	}


	function pre_add_edit() {
		//$polling_category = $this->mod_global->get_options('polling_category' , 'pollc_id' , 'pollc_name' , "pollc_status = 'Active'" , 'pollc_id');
		//$this->sci->assign('polling_category' , $polling_category);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function do_print($trans_id=0) {
		$this->db->where('trans_id' , $trans_id);
		$this->db->set('trans_do_printed' , 'Yes');
		$this->db->set('trans_do_print_date' , 'NOW()', false);
		$this->db->update('transaction');
		$this->session->set_confirm(1);
		redirect( $this->session->get_bread('list') );
	}

	function set_tracking_number() {
		$trans_id = $this->input->post('trans_id');
		$trans_tn_number = $this->input->post('trans_tn_number');

		foreach( $trans_id as $k=>$tmp) {
			if($trans_tn_number[$k] != '' ){
				$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
				$this->db->join('shipping_method sm' , 'sm.sm_id = transaction.trans_shipping_method_id' , 'left');
				$this->db->where('trans_id' , $tmp);
				$res = $this->db->get('transaction');
				$transaction = $res->row_array();
				$this->sci->assign('transaction' , $transaction);

				$this->db->where('trans_id' , $tmp);
				$this->db->set('trans_tn_number' , $trans_tn_number[$k] );
				$this->db->set('trans_tn_entered' , 'Yes' );
				$this->db->set('trans_tn_entry' , date('Y-m-d H:i:s') );
				$ok = $this->db->update('transaction');

				//send voucher if valid
				$this->load->model('mod_voucher');
				$this->mod_voucher->assign_voucher_referral($transaction['m_id']);

				$this->sci->assign('trans_tn_number' , $trans_tn_number[$k]);
				$html = $this->sci->fetch('admin/delivery_tracking/email_tracking.htm');

				$this->load->library('email');
				$config['mailtype'] = 'html';
				$config['charset'] = 'iso-8859-1';
				$this->email->initialize($config);
				$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
				$this->email->to($transaction['m_email']);
				$this->email->subject( 'Your order has been delivered' );
				$this->email->message($html);

				$ok = $this->email->send();
			}
		}

		$this->session->set_confirm(1);
		redirect( $this->session->get_bread('list') );
	}




}
