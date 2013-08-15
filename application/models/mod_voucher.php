<?php

class Mod_voucher extends MY_Model {

	var $table_name = 'voucher';
	var $id_field = 'p_id';
	var $entry_field = 'p_entry';
	var $stamp_field = 'p_stamp';
	var $status_field = 'p_status';
	var $deletion_field = 'p_deletion';
	var $order_by = 'p_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}

	function get_all_type() {
		$res = $this->db->get('voucher_type');
		$type = $res->result_array();
		return $type;
	}

	function get_type_by_code($vt_code='normal') {
		$this->db->where('vt_code' , $vt_code);
		$res = $this->db->get('voucher_type');
		$type = $res->row_array();
		return $type;
	}

	function get_valid_voucher($v_code="", $m_id=0) {
		$this->db->where('v_code' , $v_code);
		$this->db->where('m_id' , $m_id);
		$this->db->where('v_start_date <=' , date('Y-m-d H:i:s') );
		$this->db->where('v_end_date >' , date('Y-m-d H:i:s') );
		$this->db->where('v_status' , 'New');
		$res = $this->db->get('voucher');
		$voucher = $res->row_array();
		return $voucher;
	}

	//generate code
	function generate_code($vs_code=0) {
		$prefix = "";
		$prefixa = parse_str($vs_code);
		$prefixa = str_repeat("0", (4-strlen($vs_code))). $vs_code;
		$prefix = $prefix.$prefixa;
		$digit = 9; //4vsc+9code+2pin+1luhn

		// Get latest gift card
		$this->db->like('v_code' , $prefix);
		$this->db->order_by('v_code' , 'DESC');
		$this->db->limit(1);
		$res = $this->db->get('voucher');
		$last_voucher = $res->row_array();
		$pin = rand(10,99);

		if ( sizeof($last_voucher) > 0) {
			$code = $last_voucher['v_code'];
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

	//get all voucher promo
	function get_all_voucher_promo() {
		$userinfo = $this->session->get_userinfo('member');
		$this->db->join('voucher_type vt' , 'vt.vt_id = voucher_set.vt_id' , 'left');
		$this->db->where('vt_code' , 'promo');
		$this->db->where('vs_status' , 'Active');
		$res = $this->db->get('voucher_set');
		$temp_voucher= $res->result_array();
		$voucher_promo = array();
		foreach($temp_voucher as $k=>$tmp) {
			$this->db->where('vs_id' , $tmp['vs_id']);
			$this->db->where('m_id' , $userinfo['m_id']);
			$this->db->where('v_used' , 'Yes');
			$res = $this->db->get('voucher');
			$vouc = $res->row_array();
			if(!$vouc) {
				$voucher_promo[] = $tmp;
			}
		}
		return $voucher_promo;
	}

	//assign voucher referal
	function assign_voucher_referral($giver_m_id=0) {
		//get giver member
		$this->db->where('m_id' , $giver_m_id);
		$this->db->where('m_status' , 'Active');
		$res = $this->db->get('member');
		$member = $res->row_array();
		if(!$member) { return FALSE; }

		//get voucher referral
		$this->db->join('voucher_type vt' , 'vt.vt_id = voucher_set.vt_id' , 'left');
		$this->db->where('vt_code' , 'referral');
		$this->db->where('vs_status' , 'Active');
		$res = $this->db->get('voucher_set');
		$voucher_set = $res->row_array();
		if(!$voucher_set) { return FALSE; }

		$next_code = $this->generate_code($voucher_set['vs_code']);

		//if this is has a referral, and doesn't have first purchase
		if( ($member['m_referal_id'] != '0') && ($member['m_first_purchase'] == 'No') ) {
			//generate voucher
			$next_code = $this->generate_code($voucher_set['vs_code']);
			$now = date('Y-m-d H:i:s');
			$start = date_future($now,"+1 week");
			$end = date_future($start,"+3 month");
			$this->db->set('v_code' , $next_code);
			$this->db->set('m_id' , $member['m_referal_id']);
			$this->db->set('vs_id' , $voucher_set['vs_id']);
			$this->db->set('v_nominal' , $voucher_set['vs_nominal']);
			$this->db->set('v_entry' , 'NOW()', false);
			$this->db->set('v_start_date' , $start);
			$this->db->set('v_end_date' , $end);
			$this->db->set('v_used' , "No");
			$this->db->set('v_open' , "True");
			$this->db->set('v_status' , "New");
			$this->db->insert('voucher');
			//set first purchase
			$this->db->set('m_first_purchase' , 'Yes');
			$this->db->where('m_id' , $member['m_id']);
			$this->db->update('member');
		}
	}
	
	function send_email_to_receiver($v_id=0) {
		$this->db->join('voucher_set' , 'voucher_set.vs_id = voucher.vs_id' , 'left');
		$this->db->join('member' , 'member.m_id = voucher.m_id' , 'left');
		$this->db->where('v_id' , $v_id);
		$res = $this->db->get('voucher');
		$voucher = $res->row_array();
		
		if(!$voucher) { return FALSE; }
		
		$this->sci->assign('voucher' , $voucher);
		$html = $this->sci->fetch('admin/voucher/email_to_receiver.htm');
		
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($voucher['m_email']);
		$this->email->subject( 'You have received a Voucher' );
		$this->email->message($html);

		$ok = $this->email->send();
		
		$this->db->where('v_id' , $v_id);
		$this->db->set('v_is_sent' , 'Yes');
		$this->db->update('voucher');
	}
	
	
	
	function make_referal_voucher($trans_id=0) {
		$today = date('Y-m-d H:i:s');
		
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$transaction = $res->row_array();
		
		//$m_id = $transaction['m_id'];
		//get this member
		$this->db->where('m_id' , $transaction['m_id']);
		$res = $this->db->get('member');
		$member = $res->row_array();
		//get referal
		$this->db->where('m_id' , $member['m_referal_id']);
		$res = $this->db->get('member');
		$referal = $res->row_array();
		
		//run if there is referal
		if($referal) {
			$m_id = $referal['m_id'];
			  
			$this->db->where('m_id' , $m_id);
			$this->db->where('v_type' , 'Referral');
			$res = $this->db->get('voucher');
			$voucher = $res->row_array();
			
			if(!$voucher) { //kalo dia blum punya voucher 
				$this->db->where('vs_type' , 'Referral');
				$this->db->where('vs_status' , 'Active');
				$res = $this->db->get('voucher_set');
				$vs = $res->row_array(); 
				
				$atoday = date('Y-m-d');
				//set start date +1 week
				$xtarget = strtotime($transaction['trans_delivered_date'] . " +1 Week");
				$start_date = mdate("%Y-%m-%d %H:%i:%s", $xtarget);
				$atarget = strtotime($atoday . " +3 Month");
				$end_date = mdate("%Y-%m-%d", $atarget);
				//print $today."  ".$target;
				
				$this->load->model('mod_voucher');
				$next_code = $this->generate_code($vs['vs_code']); 
				$this->db->set('vs_id' , $vs['vs_id']);
				$this->db->set('m_id' , $m_id);
				$this->db->set('v_type' , 'Referral');
				$this->db->set('v_nominal' , $vs['vs_nominal']);
				$this->db->set('v_open' , 'True');
				$this->db->set('v_start_date' , $start_date);
				$this->db->set('v_end_date' , $end_date);
				$this->db->set('v_code' , $next_code);
				$this->db->set('v_entry' , 'NOW()', FALSE);
				$this->db->insert('voucher');
				
				$cron_processed = TRUE; 
			} 
		} 
		
		
	}


}

?>
