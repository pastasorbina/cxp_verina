<?php

class Cron extends CI_Controller {

	var $mod_title = '';
	var $cl_code='article';

	function __construct() {
		parent::__construct();
	}
	
	function run() {
		$this->writetext();
	}

	function sendmail(){
		$this->load->library('email');

		$this->email->from('pastasorbina@gmail.com', 'William');
		$this->email->to('pastasorbina@gmail.com');

		$this->email->subject('Email Test');
		$this->email->message('Testing the email class.');

		$this->email->send();
		echo $this->email->print_debugger();
	}

	function debug2() {
		echo "printed";
	}

	function debug() {
		$res = $this->db->get('config');
		$config = $res->result_array();
		foreach($config as $k=>$tmp) {
			echo $tmp['c_value']; echo "\r";
		}
	}

	function writetext($text = "writetext"){
		$myFile = "./text.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		$date = date('Y-m-d H:i:s');
		$stringData = "$date : $text \n";
		fwrite($fh, $stringData);
		fclose($fh);
	}


	function cart_check() {
		$this->load->library('icart');

		$this->db->where('c_key' , 'cart_timeout');
		$res = $this->db->get('config');
		$config = $res->row_array();
		$cart_timeout = $config['c_value'];

		$this->db->where('cart_status' , "Active");
		$res = $this->db->get('cart');
		$cart = $res->result_array();

		foreach($cart as $k=>$tmp) {
			$now = date('Y-m-d H:i:s');
			$cart_entry = $tmp['cart_entry'];
			$deadline = date_future($cart_entry, "+".$cart_timeout." second");
			if($now > $deadline) {
				$passed = TRUE;
				$config = array();
				$config['is_auto'] = 'Yes';
				$ok = $this->icart->remove($tmp['cart_id'], $config);
			} else {
				$passed = FALSE;
			}
		}
	}

	function check_pending_confirmation() {
		$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_status' , 'Active');
		$this->db->where('trans_payment_status' , 'Unconfirmed');
		$res = $this->db->get('transaction');
		$transaction = $res->result_array();
		foreach($transaction as $k=>$tmp) {
			$now = date('Y-m-d H:i:s');
			$entry = $tmp['trans_entry'];
			$deadline = date_future($entry, "+24 hour"); 

			if($now < $deadline) {

				//send email
				$email = $tmp['m_email'];
				$html = $this->pending_confirmation_email($tmp['trans_id']);
				$this->load->library('email');
				$config['mailtype'] = 'html';
				$config['charset'] = 'iso-8859-1';
				$this->email->initialize($config);
				$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
				$this->email->to($email);
				$this->email->subject( 'Pengingat Pembayaran' );
				$this->email->message($html);

				$ok = $this->email->send();
				
				//update changes
				$this->db->where('trans_id' , $tmp['trans_id']);
				$this->db->set('trans_reminder_count' , $tmp['trans_reminder_count']++ );
				$this->db->update('transaction');
			} else {
			} 
		}
	}
	
	
	//FUNCTIONING CONFIRMATION REMINDER
	function crd($iteration = '+1 hour' ) {
		$this->writetext('confirmation remider');
		
		//SET ITERATION TO SEND EACH HOUR
		//$iteration = '+1 hour';
		
		$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_status' , 'Active');
		$this->db->where('trans_payment_status' , 'Unconfirmed');
		$res = $this->db->get('transaction');
		$transaction = $res->result_array();
		
		$subprocess = array();
		$processme = array();
		 
		//SET NOW setiap jam XX-XX-XX XX:00:00 sampai ke 1 jam sebelumnya
		$now = date('Y-m-d H:00:00');
		$n_unix = strtotime($now);
		$nb = date_future($now, "-1 hour");
		$nb_unix = strtotime($nb);
		
		foreach($transaction as $k=>$tmp) { 
			$trans_entry = $tmp['trans_entry'];
			$max_deadline = date_future($trans_entry, "+24 hour"); //maximum deadline
			$reminder = date_future($trans_entry, $iteration); //jam reminder per iterasi
			$reminder_unix = strtotime($reminder);
			
			$email = $tmp['m_email'];
			$trans_id = $tmp['trans_id'];
			$trans_entry = $tmp['trans_entry'];
			$status = $tmp['trans_payment_status'];
			 
			if($now < $max_deadline) { //selama masih dibawah 24 jam, jalankan
				$subprocess[] = $tmp;  
				if( $reminder_unix > $nb_unix && $reminder_unix < $n_unix ) {
					// apabila reminder time di antara saat ini dengan 1 jam sebelumnya, jalankan 
					$processme[] = $tmp; //register processme 
				}  
			} //endif 
		} //endforeach
		  
		//DO PROCESS
		if(sizeof($processme) == 0) {
			$this->db->set('action' , 'Confirmation Reminder');
			$this->db->set('desc' , 'Nothing Happened');
			$this->db->set('entry_time' , date('Y-m-d H:i:s') );
			$this->db->insert('cron_log');
		} else {
			foreach($processme as $k=>$tmp) {
				$email = $tmp['m_email'];
				$trans_id = $tmp['trans_id'];
				$trans_entry = $tmp['trans_entry'];
				$status = $tmp['trans_payment_status'];
				$count = $tmp['trans_reminder_count'];  
				
				//update changes and reminder count 
				$this->db->where('trans_id' , $trans_id);
				$this->db->set('trans_reminder_count' , ($count+1) );
				$this->db->update('transaction');
				
				//cron log
				$desc = "trans_id= $trans_id, count= $count, entry= $trans_entry, n=$now, nb=$nb";
				$this->db->set('action' , 'Confirmation Reminder');
				$this->db->set('desc' , $desc);
				$this->db->set('entry_time' , date('Y-m-d H:i:s') );
				$this->db->insert('cron_log');
				
				$html = $this->pending_confirmation_email($trans_id);
				$this->load->library('email');
				$config['mailtype'] = 'html';
				$config['charset'] = 'iso-8859-1';
				$this->email->initialize($config);
				$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
				$this->email->to($email);
				$this->email->subject( 'Pengingat Pembayaran' );
				$this->email->message($html);
			}
		}
	}
	
	
	function crd_test($iteration = '+1 hour' ) {
		$this->writetext('confirmation remider');
		
		//SET ITERATION TO SEND EACH HOUR
		//$iteration = '+1 hour';
		
		$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_status' , 'Active');
		$this->db->where('trans_payment_status' , 'Unconfirmed');
		$res = $this->db->get('transaction');
		$transaction = $res->result_array();
		
		$subprocess = array();
		$processme = array();
		 
		//SET NOW setiap jam XX-XX-XX XX:00:00 sampai ke 1 jam sebelumnya
		$now = date('Y-m-d H:00:00');
		$n_unix = strtotime($now);
		$nb = date_future($now, "-1 hour");
		$nb_unix = strtotime($nb);
		
		foreach($transaction as $k=>$tmp) { 
			$trans_entry = $tmp['trans_entry'];
			$max_deadline = date_future($trans_entry, "+24 hour"); //maximum deadline
			$reminder = date_future($trans_entry, $iteration); //jam reminder per iterasi
			$reminder_unix = strtotime($reminder);
			
			$email = $tmp['m_email'];
			$trans_id = $tmp['trans_id'];
			$trans_entry = $tmp['trans_entry'];
			$status = $tmp['trans_payment_status'];
			 
			if($now < $max_deadline) { //selama masih dibawah 24 jam, jalankan
				$subprocess[] = $tmp;  
				if( $reminder_unix > $nb_unix && $reminder_unix < $n_unix ) {
					// apabila reminder time di antara saat ini dengan 1 jam sebelumnya, jalankan 
					$processme[] = $tmp; //register processme 
				}  
			} //endif 
		} //endforeach
		  
		//DO PROCESS
		if(sizeof($processme) == 0) {
			$this->db->set('action' , 'Test Confirmation Reminder');
			$this->db->set('desc' , 'Nothing Happened');
			$this->db->set('entry_time' , date('Y-m-d H:i:s') );
			$this->db->insert('cron_log');
		} else {
			foreach($processme as $k=>$tmp) {
				$email = $tmp['m_email'];
				$trans_id = $tmp['trans_id'];
				$trans_entry = $tmp['trans_entry'];
				$status = $tmp['trans_payment_status'];
				$count = $tmp['trans_reminder_count'];  
				
				$this->db->set('action' , 'Test Conf Reminder');
				$desc = "trans_id= $trans_id, count= $count, entry= $trans_entry, n=$now, nb=$nb";
				$this->db->set('entry_time' , date('Y-m-d H:i:s') );
				$this->db->insert('cron_log');
				
				//update changes and reminder count 
				$this->db->where('trans_id' , $trans_id);
				$this->db->set('trans_reminder_count' , ($count+1) );
				$this->db->update('transaction');
			}
		}
	}
	
	
	
	
	function pending_confirmation_email($id=4) {
		//get bank account numbers
		$this->db->where('ba_status' , 'Active');
		$res = $this->db->get('bank_account');
		$bank_account = $res->result_array();
		$this->sci->assign('bank_account' , $bank_account);

		$this->load->model('mod_transaction');
		$transaction = $this->mod_transaction->get_transaction_by_id($id);
		$this->sci->assign('transaction' , $transaction);

		$entry = $transaction['trans_entry'];
		$deadline = date_future($entry, "+12 hour");
		$this->sci->assign('deadline' , $deadline);

		$this->db->where('m_id' , $transaction['m_id']);
		$res = $this->db->get('member');
		$member = $res->row_array();
		$this->sci->assign('member' , $member);

		$html = $this->sci->fetch('cron/remind_confirmation.htm');
		echo $html;
		return $html;
	}
	
	
	/*
	 * TRANSACTION_CANCEL
	 * cron active
	 * cancel transaction that is expired / 1 day old
	 */
	
	function transaction_cancel() {
		$html2 = '';
		$today =  date('Y-m-d H:i:s');
		$target_expired = strtotime($today . " -1 Day");
		$target_expired = mdate("%Y-%m-%d %H:%i:%s", $target_expired);
		$cron_processed = FALSE;
		
		$this->db->join('member m' , 'm.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_status' , 'Active');
		$this->db->where('trans_payment_status = ' , "Unconfirmed");
		$this->db->where('trans_entry <' , $target_expired);
		$res = $this->db->get('transaction');
		$tobe_cancelled = $res->result_array();
		
		foreach($tobe_cancelled as $k=>$tmp) {  
			$trans_date = $tmp['trans_entry'];
			$this->sci->assign('trans_date' , $trans_date);
			$expiry = strtotime($tmp['trans_entry'] . " +1 Day");
			$expiry = mdate("%Y-%m-%d %H:%i:%s", $expiry);
			$this->sci->assign('expiry' , $expiry);
			
			$this->db->join('product_quantity pq' , 'pq.pq_id = transaction_detail.pq_id' , 'left');
			$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
			$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
			$this->db->where('trans_id' , $tmp['trans_id']);
			$res = $this->db->get('transaction_detail');
			$trans_detail = $res->result_array();
			
			$this->load->model('mod_stock');
		
		//update stock
			foreach($trans_detail as $k2=>$tmp2) {
				$config = array();
				$config['id'] = $tmp2['pq_id'];
				$config['change'] = $tmp2['transd_quantity'];
				$config['trans_id'] = $tmp['trans_id'];
				$config['note'] = "Cancellation Trans ID: ". $tmp['trans_id'];
				$config['action'] = "stock_in";
				$config['u_id'] = $this->userinfo['u_id'];
				$this->mod_stock->stock_in($config);
			}
			
		//update status history
			$this->db->set('trans_id' , $tmp['trans_id'] );
			$this->db->set('tsh_from' , $tmp['trans_payment_status'] );
			$this->db->set('tsh_to' , 'Cancelled');
			$this->db->set('tsh_reason' , 'Expired' );
			$this->db->set('u_id' , 0 );
			$this->db->set('tsh_entry' , 'NOW()', FALSE);
			$this->db->insert('transaction_status_history');
			
		//set transaction as cancelled
			$this->db->where('trans_id' ,  $tmp['trans_id'] );
			$this->db->set('trans_payment_status' , "Cancelled");
			$this->db->set('trans_cancel_date' , 'NOW()', FALSE);
			$this->db->update('transaction');
		
		//create and send email	
			$total_cart = 0;
			foreach($trans_detail as $k2=>$tmp2) {
				$option = unserialize($tmp2['transd_option']); 
				foreach($option as $k3=>$tmp3) {
					$transaction_detail[$k2][$tmp3[0]] = $tmp3[1];
				}
				$total_cart += $tmp2['transd_subtotal'];
			}
			$this->sci->assign('total_cart' , $total_cart);
			$this->sci->assign('transaction' , $tmp);
			$this->sci->assign('transaction_detail' , $trans_detail);
	
			$html = $this->sci->fetch('admin/transaction/email_cancel_transaction.htm'); 
			$email = $tmp['m_email']; 
			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
			$this->email->to($email);
			$this->email->subject( 'Transaksi Anda Dibatalkan' );
			$this->email->message($html); 
			$ok = $this->email->send();
			
			$html2 = $html2.$html;
			$cron_processed = TRUE;
		}
		
		if($cron_processed == TRUE ) {
			$head = "these items are processed : <br><br>";
			$html2 = $head.$html2;
			//notify 
			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
			$this->email->to('pastasorbina@gmail.com');
			$this->email->subject( 'Cron Report [transaction expired]' );
			$this->email->message($html2); 
			$ok = $this->email->send(); 
		}
		
	}
	
	
	function voucher_birthday() {
		//die();
		$today = date('Y-m-d');  
		$today_day = date('d');
		$today_month = date('m');  
		$target = strtotime($today . " +2 Week");  
		$target_day = mdate("%d", $target);
		$target_month = mdate("%m", $target); 
		
		$this->db->where('m_status' , 'Active');
		$this->db->where('MONTH(m_birthday)' , $today_month);
		$this->db->where('DATE(m_birthday) >=' , $today_day);
		$this->db->where('DATE(m_birthday) <=' , $target_day); 
		$res = $this->db->get('member');
		$tmpmember = $res->result_array();
		
		$member = array();
		$html = '';
		$affect_row = 0;
		$i=0;
		$cron_processed = FALSE;
		
		if(sizeof($tmpmember) > 0) {
			foreach($tmpmember as $k=>$tmp) { 
				$this->db->where('m_id' , $tmp['m_id']);
				$this->db->where('v_type' , 'Birthday');
				$res = $this->db->get('voucher');
				$bdvoucher = $res->row_array();
				
				if(!$bdvoucher) {
					$this->db->where('vs_type' , 'Birthday');
					$res = $this->db->get('voucher_set');
					$vs = $res->row_array(); 
					
					$atoday = date('Y-m-d');
					$atarget = strtotime($atoday . " +2 Month");
					$atarget = mdate("%Y-%m-%d", $atarget); 
					
					$this->load->model('mod_voucher');
					$next_code = $this->mod_voucher->generate_code($vs['vs_code']);
					
					//create new voucher
					$this->db->set('vs_id' , $vs['vs_id']);
					$this->db->set('m_id' , $tmp['m_id']);
					$this->db->set('v_type' , 'Birthday');
					$this->db->set('v_nominal' , $vs['vs_nominal']);
					$this->db->set('v_open' , 'True');
					$this->db->set('v_start_date' , $atoday);
					$this->db->set('v_end_date' , $atarget);
					$this->db->set('v_code' , $next_code);
					$this->db->set('v_entry' , 'NOW()', FALSE);
					$this->db->insert('voucher');
					$insert_v_id = $this->db->insert_id();
				
					//send email
					$this->load->model('mod_voucher');
					$this->mod_voucher->send_email_to_receiver($insert_v_id);
					
					$i++;
					$affect_row = $affect_row + $this->db->affected_rows();
					$html = $html . $tmp['m_id']." - ".$tmp['m_login']." - ".$tmp['m_birthday']." - make voucher : ".$vs['vs_id']." - ".$next_code." - ".$vs['vs_nominal']." <br>";
					$cron_processed = TRUE;
				} 
			} 
			if($cron_processed == TRUE) {
				$head = "(".site_url().") these items are processed : <br><br>";
				$html = $head.$html;
				$html = $html.$affect_row;
				//notify 
				$this->load->library('email');
				$config['mailtype'] = 'html';
				$config['charset'] = 'iso-8859-1';
				$this->email->initialize($config);
				$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
				$this->email->to('pastasorbina@gmail.com');
				$this->email->subject( 'Cron Report [voucher bday]' );
				$this->email->message($html); 
				$ok = $this->email->send();
			}
		}
		
		
	}
	
	function voucher_queue() {
		$this->db->where('v_start_date >=' ,  date('Y-m-d') );
		$this->db->where('v_is_sent' , 'No');
		$res = $this->db->get('voucher');
		$vouchers = $res->result_array();
		$affect_row = '';
		$this->load->model('mod_voucher');
		foreach($vouchers as $k=>$tmp) {
			$this->mod_voucher->send_email_to_receiver($tmp['v_id']);
			$affect_row .= $tmp['v_id']." / ";
		}
		
		$myFile = "./text.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		$date = date('Y-m-d H:i:s');
		$stringData = "run voucher_queue $date = ".$affect_row." \n";
		fwrite($fh, $stringData);
		fclose($fh);
		$head = "(".site_url().") these items are processed : <br><br>";
		$html = $head.$html;
		$html = $html.$affect_row;
		//notify 
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to('pastasorbina@gmail.com');
		$this->email->subject( 'Cron Report [voucher queue]' );
		$this->email->message($html); 
		$ok = $this->email->send();
	}
	
	function voucher_referal() {
		$today = date('Y-m-d H:i:s');
		$this->db->where('m_status' , 'Active');
		$this->db->where('m_referal_id !=' , '0'); 
		$res = $this->db->get('member');
		$tmpmember = $res->result_array();
		$member = array();
		$i=0;
		$cron_processed = FALSE;
		//print_r($tmpmember);
		
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
					$xtarget = strtotime($transaction['trans_delivered_date'] . " +1 Week");
					$target = mdate("%Y-%m-%d %H:%i:%s", $xtarget);
					print $today." ".$target."\n";
					
						if($today >= $target) { //kalo hari ini lebih besar dari pada target, dijalankan 1 minggu setelah tanggal delivered
							//if(TRUE) {
							$this->db->where('m_id' , $tmp['m_referal_id']);
							$this->db->where('v_type' , 'Referral');
							$res = $this->db->get('voucher');
							$voucher = $res->row_array();
							
							if(!$voucher) { //kalo dia blum punya voucher
								
								//run HERE
								
								$this->db->where('m_id' , $tmp['m_referal_id']);
								$res = $this->db->get('member');
								$referal = $res->row_array(); 
								$this->db->where('vs_type' , 'Referral');
								$this->db->where('vs_status' , 'Active');
								$res = $this->db->get('voucher_set');
								$vs = $res->row_array(); 
								
								$atoday = date('Y-m-d');
								$atarget = strtotime($atoday . " +3 Month");
								$atarget = mdate("%Y-%m-%d", $atarget);
								//print $today."  ".$target;
								
								$this->load->model('mod_voucher');
								$next_code = $this->mod_voucher->generate_code($vs['vs_code']); 
								$this->db->set('vs_id' , $vs['vs_id']);
								$this->db->set('m_id' , $tmp['m_referal_id']);
								$this->db->set('v_type' , 'Referral');
								$this->db->set('v_nominal' , $vs['vs_nominal']);
								$this->db->set('v_open' , 'True');
								$this->db->set('v_start_date' , $atoday);
								$this->db->set('v_end_date' , $atarget);
								$this->db->set('v_code' , $next_code);
								$this->db->set('v_entry' , 'NOW()', FALSE);
								$this->db->insert('voucher');
								
								$cron_processed = TRUE;
							}
							
						} 
					
				}  
			} 
		}
		
		
		if($cron_processed == TRUE) {
			$head = "(".site_url().") these items are processed : <br><br>";
			$html = $head.$html;
			$html = $html.$affect_row;
			//notify 
			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
			$this->email->to('pastasorbina@gmail.com');
			$this->email->subject( 'Cron Report [voucher referal]' );
			$this->email->message($html); 
			$ok = $this->email->send();
		}
		
	}


}
