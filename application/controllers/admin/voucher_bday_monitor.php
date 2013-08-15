<?php
class Voucher_bday_monitor extends MY_Controller {

	var $mod_title = 'Transaction Monitor';
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
		$this->userinfo = $this->session->get_userinfo();
		$this->load->model('mod_transaction');
	}
	
	function _load_sidebar() {
		$html = $this->sci->fetch('admin/transaction_monitor/sidebar.htm');
		$this->sci->assign('sidebar' , $html);
	}
 
	
	function index($pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		redirect( $this->mod_url."need_to_set" );
		$this->session->set_bread('list');
		//$this->_load_sidebar();
		
		$today = date('Y-m-d H:i:s');
		$this->sci->assign('today' , $today);
		
		$this->db->where('m_status' , 'Active');
		$this->db->where('m_birthday !=' , '0000-00-00');
		$res = $this->db->get('member');
		$member = $res->result_array();
		
		foreach($member as $k=>$tmp) {
			$this->db->where('m_id' , $tmp['m_id']);
			$res = $this->db->get('voucher');
			$voucher = $res->result_array();
			$member[$k]['voucher'] = $voucher;
		}
		
		$this->sci->assign('member' , $member);
		$this->sci->da('index.htm');
	}
	
	function need_to_set($pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		//$this->_load_sidebar();
		
		$today = date('Y-m-d');
		$this->sci->assign('today' , $today);
		
		$today_day = date('d');
		$today_month = date('m');
		$this->sci->assign('today_day' , $today_day);
		$this->sci->assign('today_month' , $today_month);
		
		$target = strtotime($today . " +2 Week");
		//$target = mdate("%Y-%m-%d", $target);
		//$this->sci->assign('target' , $target);
		
		$target_day = mdate("%d", $target);
		$target_month = mdate("%m", $target);
		$this->sci->assign('target_day' , $target_day);
		$this->sci->assign('target_month' , $target_month);
		
		$this->db->where('m_status' , 'Active');
		$this->db->where('MONTH(m_birthday)' , $today_month);
		$this->db->where('DATE(m_birthday) >=' , $today_day);
		$this->db->where('DATE(m_birthday) <=' , $target_day); 
		$res = $this->db->get('member');
		$tmpmember = $res->result_array();
		
		$member = array();
		$i=0;
		
		foreach($tmpmember as $k=>$tmp) {
			
			$this->db->where('m_id' , $tmp['m_id']);
			$this->db->where('v_type' , 'Birthday');
			$res = $this->db->get('voucher');
			$bdvoucher = $res->row_array();
			
			if(!$bdvoucher) {
				$member[$i] = $tmp;
				$this->db->where('m_id' , $tmp['m_id']);
				$res = $this->db->get('voucher');
				$voucher = $res->result_array();
				$member[$i]['voucher'] = $voucher;	
				$i++;
			}
			
			
		}
		
		$this->sci->assign('member' , $member);
		$this->sci->da('need_to_set.htm');
	}

 
	function give_voucher($m_id=0) {
		$this->db->where('vs_type' , 'Birthday');
		$this->db->where('vs_status' , 'Active');
		$res = $this->db->get('voucher_set');
		$vs = $res->row_array();
		//print_r($vs);
		
		$today = date('Y-m-d');
		$target = strtotime($today . " +2 Month");
		$target = mdate("%Y-%m-%d", $target);
		//print $today."  ".$target;
		
		$this->load->model('mod_voucher');
		$next_code = $this->mod_voucher->generate_code($vs['vs_code']);
		
		$this->db->set('vs_id' , $vs['vs_id']);
		$this->db->set('m_id' , $m_id);
		$this->db->set('v_type' , 'Birthday');
		$this->db->set('v_nominal' , $vs['vs_nominal']);
		$this->db->set('v_open' , 'True');
		$this->db->set('v_start_date' , $today);
		$this->db->set('v_end_date' , $target);
		$this->db->set('v_code' , $next_code);
		$this->db->set('v_entry' , 'NOW()', FALSE);
		$this->db->insert('voucher');
		
		redirect( $this->mod_url."need_to_set" );
	}
	
	
	function pending_referal() {
		
		$this->db->where('vs_type' , 'Referral');
		$this->db->where('vs_status' , 'Active');
		$res = $this->db->get('voucher_set');
		$vs = $res->row_array();
		
		$this->db->where('m_status' , 'Active');
		$this->db->where('m_referal_id !=' , '0'); 
		$res = $this->db->get('member');
		$tmpmember = $res->result_array();
		$member = array();
		$i=0;
		
		foreach($tmpmember as $k=>$tmp) {
			$this->db->start_cache();
			$this->db->where('m_id' , $tmp['m_id']);
			$this->db->where('trans_status' , 'Active');
			$this->db->order_by('trans_entry' , 'DESC');
			$this->db->where('trans_payment_status' , 'Delivered');
			$this->db->stop_cache();
			$total_transaction = $this->db->count_all_results('transaction');
			$res = $this->db->get('transaction');
			$transaction = $res->row_array();
			$this->db->flush_cache();
			 
			if($total_transaction > 0) {
				$member[$i] = $tmp;
				$member[$i]['transaction'] = $transaction;
				$member[$i]['total_transaction'] = $total_transaction;
				
				if($total_transaction > 0) {
					$target = strtotime($transaction['trans_delivered_date'] . " +1 Week");
					$target = mdate("%Y-%m-%d %H:%i:%s", $target);
				} else {
					$target = 0;
				}
				$member[$i]['target'] = $target;
				
				$this->db->where('m_id' , $tmp['m_referal_id']);
				$res = $this->db->get('member');
				$referal = $res->row_array();
				$member[$i]['referal'] = $referal;
				
				$this->db->where('m_id' , $tmp['m_referal_id']);
				$this->db->where('v_type' , 'Referral');
				$res = $this->db->get('voucher');
				$voucher = $res->row_array();
				$member[$i]['voucher'] = $voucher;
				$i++;
			} 
		}
		
		$this->sci->assign('member' , $member); 
		$this->sci->da('pending_referal.htm');
	}

	function give_voucher_referal($m_id=0) {
		$this->db->where('vs_type' , 'Referral');
		$this->db->where('vs_status' , 'Active');
		$res = $this->db->get('voucher_set');
		$vs = $res->row_array();
		//print_r($vs);
		
		$today = date('Y-m-d');
		$target = strtotime($today . " +3 Month");
		$target = mdate("%Y-%m-%d", $target);
		//print $today."  ".$target;
		
		$this->load->model('mod_voucher');
		$next_code = $this->mod_voucher->generate_code($vs['vs_code']);
		
		$this->db->set('vs_id' , $vs['vs_id']);
		$this->db->set('m_id' , $m_id);
		$this->db->set('v_type' , 'Referral');
		$this->db->set('v_nominal' , $vs['vs_nominal']);
		$this->db->set('v_open' , 'True');
		$this->db->set('v_start_date' , $today);
		$this->db->set('v_end_date' , $target);
		$this->db->set('v_code' , $next_code);
		$this->db->set('v_entry' , 'NOW()', FALSE);
		$this->db->insert('voucher');
		
		redirect( $this->mod_url."pending_referal" );
	}


}
