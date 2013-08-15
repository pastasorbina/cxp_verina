<?php
class Sandbox extends MY_Controller {

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('ADMIN'), 'admin');
		//$this->sci->assign('use_ajax' , TRUE);
		$this->check();
	}
 

	//check if not run from CLI, then die
	function check() {
		if( isset($_SERVER['HTTP_USER_AGENT']) ) {
			print "";
			die();
		}else {
			print "[starting..] \n";
		}

	}

	function viewmail() {
		$this->sci->d('admin/sandbox/mail_template.htm', TRUE);

	}
	function sendmail() {
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$this->email->initialize($config);
		$this->email->from('wigo@ravintola.web.id', 'William');
		$this->email->to('pastasorbina@gmail.com');
		$this->email->subject('Email Test');
		$message = $this->sci->fetch('admin/sandbox/mail_template.htm');
		$this->email->message($message);
		$this->email->send();
		echo $this->email->print_debugger();
	}

	function change_engine() {
		$db_name = $this->db->database;
		$prefix = "Tables_in_$db_name";
		$res = $this->db->query('SHOW TABLES');
		$result = $res->result_array();
		$tables = array();

		$myisam = array();
		//$myisam = array('content', 'config', );
		foreach($result as $k=>$tmp){
			$table = $tmp[$prefix];
			if(in_array($table, $myisam)) {
				$query = "ALTER TABLE `".$table."` ENGINE = MYISAM";
				$engine = "MYISAM";
			} else {
				$query = "ALTER TABLE `".$table."` ENGINE = InnoDB";
				$engine = "InnoDB";
			}
			$ok = $this->db->query($query);
			print "Change $table to -> $engine [$ok]";
			print "\n";
		}
	}

	function fix_shipping_price() {
		$this->db->where('sp_status' , 'Active');
		$res = $this->db->get('shipping_price');
		$result = $res->result_array();
		$size = sizeof($result)-1;
		foreach($result as $k=>$tmp) {
			$this->db->where('ap_id' , $tmp['ap_id']);
			$res = $this->db->get('area_province');
			$province = $res->row_array();
			if($province) { $sp_province = $province['ap_name']; } else { $sp_province=''; }

			$this->db->where('ac_id' , $tmp['ac_id']);
			$res = $this->db->get('area_city');
			$city = $res->row_array();
			if($city) { $sp_city = $city['ac_name']; } else { $sp_city=''; }

			$this->db->set('sp_province' , $sp_province);
			$this->db->set('sp_city' , $sp_city);
			$this->db->where('sp_id' , $tmp['sp_id']);
			$ok = $this->db->update('shipping_price');
			print "($k / $size) province: $sp_province / city: $sp_city";
			print "\n";
		}
	}


}
