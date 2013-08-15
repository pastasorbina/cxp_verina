<?php

class Sandbox extends CI_Controller {

	var $mod_title = '';
	var $cl_code='article';

	function __construct() {
		parent::__construct();
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

	function writetext(){
		$myFile = "./text.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		$date = date('Y-m-d H:i:s');
		$stringData = "$date\n";
		fwrite($fh, $stringData);
		fclose($fh);
		echo "written";
	}


	function cart_check() {
		$this->db->where('cart_status' , "Active");
		$res = $this->db->get('cart');
		$cart = $res->result_array();
		print_r($cart);
	}


}
