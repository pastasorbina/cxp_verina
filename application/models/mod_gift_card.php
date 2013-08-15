<?php

class Mod_gift_card extends MY_Model {

	var $table_name = 'gift_card';
	var $id_field = 'gc_id';
	var $entry_field = 'gc_entry';
	var $stamp_field = 'gc_stamp';
	var $status_field = 'gc_status';
	var $deletion_field = 'gc_deletion';
	var $order_by = 'gc_entry';
	var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}

	function get_gift_card($gc_id=0){
		$this->db->where('gc_id' , $gc_id);
		$this->db->where('m_id' , $this->userinfo['m_id']);
		$this->db->where('gc_status' , 'Active');
		$this->db->where('gc_is_used' , 'No');
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array();
		return $gift_card;
	}

	function disable($gc_id=0) {
		$this->db->where( $this->id_field , $gc_id);
		$this->db->set('gc_is_used' , 'Yes');
		$this->db->set('gc_used_date' , 'NOW()', FALSE);
		$ok = $this->db->update($this->table_name);
		return $ok;
	}

	function transfer($gc_id=0, $gc_sender_id=0, $gc_receiver_id=0){
		$this->db->trans_start();
		$this->db->where('gc_id' , $gc_id);
		$this->db->set('m_id' , $gc_receiver_id);
		$this->db->set('gc_sender_id' , $gc_sender_id);
		$this->db->set('gc_received_date' , 'NOW()', false);
		$this->db->update('gift_card');
		//log transfer
		$this->log_transfer($gc_id, $gc_sender_id, $gc_receiver_id);
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function log_transfer($gc_id=0, $gc_sender_id=0, $gc_receiver_id=0) {
		$this->db->set('gc_id' , $gc_id);
		$this->db->set('gch_sender_id' , $gc_sender_id);
		$this->db->set('gch_receiver_id' , $gc_receiver_id);
		$this->db->set('gch_entry' , 'NOW()', false);
		$this->db->insert('gift_card_history');
	}
	
	function send_email_to_receiver($gc_id=0) {
		$this->db->join('member' , 'member.m_id = gift_card.m_id' , 'left');
		$this->db->where('gc_id' , $gc_id);
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array(); 
		$this->sci->assign('gift_card' , $gift_card);
		
		if(!$gift_card) { return FALSE; }
		
		$this->sci->assign('gift_card' , $gift_card);
		$html = $this->sci->fetch('admin/gift_card/email_to_receiver.htm');
		
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($gift_card['m_email']);
		$this->email->subject( 'You have received a Giftcard' );
		$this->email->message($html);

		$ok = $this->email->send();
	}
	
	function test_view_email($gc_id=0) {
		$this->db->join('member' , 'member.m_id = gift_card.m_id' , 'left');
		$this->db->where('gc_id' , $gc_id);
		$res = $this->db->get('gift_card');
		$gift_card = $res->row_array(); 
		$this->sci->assign('gift_card' , $gift_card);
		
		if(!$gift_card) { return FALSE; }
		
		$this->sci->assign('gift_card' , $gift_card);
		$html = $this->sci->fetch('admin/gift_card/email_to_receiver.htm');
		print $html;
	}

}

?>
