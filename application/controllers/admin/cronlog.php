<?php
class Cronlog extends MY_Controller {

	var $mod_title = 'Cron Log';

	var $table_name = 'cron_log';
	var $id_field = 'id'; 
	var $entry_field = 'entry_time'; 
	var $order_field = 'entry_time';
	var $order_dir = 'DESC'; 

	var $search_in = array('action', 'desc');

	var $template_add = 'edit.htm';
	var $template_edit = 'edit.htm';


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('BANNER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->image_directory = 'userfiles/upload/';
		$this->thumb_directory = 'userfiles/upload/thumb/';
		$this->thumb_width = 80;
		$this->thumb_height = 80;
		$this->userinfo = $this->session->get_userinfo(); 
	}
	
	function index($pagelimit='50', $offset=0, $orderby='entry_time', $ascdesc='DESC', $encodedkey='') {
		$this->session->set_bread('list');
		
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		if ($pagelimit == '') $pagelimit = $this->default_pagelimit;
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
		$maindata = $this->iteration_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		
		
		$this->db->where('trans_status' , 'Active');
		//$this->db->where('trans_payment_status' , 'Unconfirmed');
		$this->db->order_by('trans_entry' , 'DESC');
		$this->db->limit(40);
		$res = $this->db->get('transaction');
		$transdata = $res->result_array();
		$this->sci->assign('transdata' , $transdata);
		$this->sci->da('index.htm');
	}
	
	
	function testcr(){
		$iteration = '+1 hour';
		$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left');
		$this->db->where('trans_status' , 'Active');
		$this->db->where('trans_payment_status' , 'Unconfirmed');
		$res = $this->db->get('transaction');
		$transaction = $res->result_array();
		
		$subprocess = array();
		$processme = array();
		 
		$now = date('Y-m-d H:00:00');
		$n_unix = strtotime($now);
		//now ini pasti setiam jam 00-00-00 XX:00:00
		$nb = date_future($now, "-1 hour");
		$nb_unix = strtotime($nb);
		
		print "now: $now // $n_unix<br>";
		print "now_unix: $nb // $nb_unix<br>";
		
		print "<h2>All Unconfirmed</h2>";
		foreach($transaction as $k=>$tmp) {
			 
			$trans_entry = $tmp['trans_entry'];
			$max_deadline = date_future($trans_entry, "+24 hour"); //maximum deadline
			$reminder = date_future($trans_entry, $iteration); //jam reminder per iterasi
			$reminder_unix = strtotime($reminder);
			
			$email = $tmp['m_email'];
			$trans_id = $tmp['trans_id'];
			$trans_entry = $tmp['trans_entry'];
			$status = $tmp['trans_payment_status'];
			
			print "reminder time: $reminder // $reminder_unix <br>"; 
			print "$trans_id / $email / $status / time: $trans_entry";
			print "<hr>";

			if($now < $max_deadline) {
				//selama masih dibawah 24 jam, jalankan
				$subprocess[] = $tmp;  
				if( $reminder_unix > $nb_unix && $reminder_unix < $n_unix ) {
					// apabila reminder time di antara saat ini dengan 1 jam sebelumnya, jalankan 
					$processme[] = $tmp; //register processme 
				}  
			} //endif
			
			
		} //endforeach
		
		//DO PROCESS 
		print "<h2>Under 24 Hours </h2>";
		foreach($subprocess as $k=>$tmp) {
			$email = $tmp['m_email'];
			$trans_id = $tmp['trans_id'];
			$trans_entry = $tmp['trans_entry'];
			$status = $tmp['trans_payment_status'];
			$count = $tmp['trans_reminder_count'];
			 
			print "$trans_id / $email / $status / time: $trans_entry";
			print "<hr>";
		}
		
		//DO PROCESS 
		print "<h2>Processed </h2>";
		foreach($processme as $k=>$tmp) {
			$email = $tmp['m_email'];
			$trans_id = $tmp['trans_id'];
			$trans_entry = $tmp['trans_entry'];
			$status = $tmp['trans_payment_status'];
			$count = $tmp['trans_reminder_count']; 
			 
			print "$trans_id / $email / $status / time: $trans_entry";
			print "<hr>";
			
			////update changes and reminder count 
			//$this->db->where('trans_id' , $trans_id);
			//$this->db->set('trans_reminder_count' , ($count+1) );
			//$this->db->update('transaction');
			
			////cron log
			//$desc = "trans_id= $trans_id, count= $tr, n=$now, nb=$now_bottom";
			//$this->db->set('action' , 'Confirmation Reminder');
			//$this->db->set('desc' , $desc);
			//$this->db->set('entry_time' , date('Y-m-d H:i:s') );
			//$this->db->insert('cron_log');
		}
	}



}
