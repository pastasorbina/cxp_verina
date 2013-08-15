<?php
class Gift_card_assign extends MY_Controller {

	var $mod_title = 'Gift Card Assigning';

	var $table_name = 'transaction';
	var $id_field = 'trans_id';
	var $status_field = 'trans_status';
	var $entry_field = 'trans_entry';
	var $stamtrans_field = 'trans_stamp';
	var $deletion_field = 'trans_deletion';
	var $order_field = 'trans_entry';
	var $order_dir = 'DESC';

	var $author_field = 'trans_author';
	var $editor_field = 'trans_editor';

	var $search_in = array('trans_id');

	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->userinfo = $this->session->get_userinfo();
		$this->sci->assign('use_ajax' , TRUE);

	}

	function index($trans_payment_status = 'Paid', $pagelimit=100, $offset=0, $orderby='trans_entry', $ascdesc='DESC', $encodedkey='') {
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
			$this->db->from('transaction');
			$this->join_setting();
			$this->where_setting();
			$this->db->where('trans_payment_status' , $trans_payment_status);
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('transaction');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$trans_payment_status/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 7;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata); 
		$maindata = $this->iteration_setting($maindata);


		//print_r($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function iteration_setting($maindata) {
		foreach($maindata as $k=>$tmp) {
			$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
			$this->db->where('trans_id' , $tmp['trans_id']);
			$res = $this->db->get('transaction_detail');
			$detail = $res->result_array();
			$maindata[$k]['detail'] = $detail;
			//$maindata[$k]['can_be_assigned'] = 'No';
			//if($tmp['trans_payment_status'] != 'Unconfirmed' AND $tmp['trans_payment_status'] != 'Confirmed' ) {
			//	$maindata[$k]['can_be_assigned'] = 'Yes';
			//}
		}
		return $maindata;
	}

	function join_setting() {
		//$this->db->join('transaction t' , 't.trans_id = transaction_detail.trans_id' , 'left');
		$this->db->join('member m' , 'm.m_id = transaction.m_id' , 'left');
		//$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
	}

	function where_setting() {
		$this->db->where("trans_status" ,"Active");
		$this->db->where('trans_type' , 'Giftcard');
		//$this->db->where('trans_payment_status !=' , 'Unconfirmed');
		//$this->db->where('trans_payment_status !=' , 'Confirmed');
		//$this->db->where('trans_payment_status !=' , 'Cancelled');
	}

	function validation_setting() {
	}

	function database_setter() {
	}


	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function do_assign($trans_id=0) { 
		
		$this->db->where('trans_id' , $trans_id);
		$this->db->where('trans_status' , 'Active');
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();
		
		$this->db->join('transaction t' , 't.trans_id = transaction_detail.trans_id' , 'left');
		$this->db->join('member m' , 'm.m_id = transaction_detail.m_id' , 'left');
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->where('transaction_detail.trans_id' , $trans_id);
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();

		$this->db->trans_start();
		
		foreach($trans_detail as $k=>$tmp ) {
			$trans_id = $tmp['trans_id'];
			$p_id = $tmp['p_id'];
			$m_id =  $tmp['m_id'];
			$quantity = floor($tmp['transd_quantity']);
			
			//iterate adding giftcard according to the number of quantity in the detail
			for($i=0; $i<$quantity; $i++) {
				
				////insert gift card
				$this->db->set('gc_nominal' , $tmp['p_giftcard_nominal'] );
				$this->db->set('gc_name' , $tmp['p_name'] );
				$this->db->set('p_id' , $p_id );
				$this->db->set('m_id' , $m_id );
				$this->db->set('gc_is_used' , 'No' );
				$this->db->set('gc_entry' , 'NOW()', false );
				$this->db->set('gc_received_date' , 'NOW()', false );
				$this->db->set('gc_expire_date' , date_future('','+3months') );
				$this->db->set('gc_author' , $this->userinfo['u_id'] );
				$next_code = $this->generate_code($p_id);
				$this->db->set('gc_code' , $next_code );
				$ok = $this->db->insert('gift_card');
				$insert_id = $this->db->insert_id();  
			}
			
			//and then set this transd as assigned
			$this->db->where('transd_id' , $tmp['transd_id'] );
			$this->db->set('transd_giftcard_assigned' , "Yes");
			$this->db->set('transd_giftcard_assigned_date' , 'NOW()', false );
			$this->db->update('transaction_detail');
			
		}
		
		////then set the transaction as delivered
		$this->db->where('trans_id' , $trans_id);
		$this->db->set('trans_payment_status' , 'Delivered');
		$this->db->set('trans_delivered_date' , 'NOW()', FALSE);
		$this->db->update('transaction');
		
		$this->db->trans_complete();
		$this->session->set_confirm(1);
		redirect( $this->session->get_bread('list') );

		 
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
