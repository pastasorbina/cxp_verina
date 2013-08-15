<?php

class Instagram extends MY_Controller {

	var $clientid = 'e72f7614adb54e30a8fcd1f0558b4828';
	var $clientsecret = '369eb8ab9dd94b1297726b4b73828d8f';

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
		$this->config->set_item('debug_mode', FALSE);

		$this->load->library('Rpc');
	}


	function index() {
		$url = "https://api.instagram.com/v1/subscriptions/";
		$param = array(
			'client_id' 		=> $this->clientid,
			'client_secret'		=> $this->clientsecret,
			'object'			=> 'user',
			'aspect'			=> 'media',
			'verify_token'		=> '',
			'callback_url'		=> 'http://localhost'
		);
		print $this->rpc->doREST($url, $param);
	}

	public function stream_list() {
		$this->sci->da('stream_list.htm');
	}

	public function get_stream(){
		$hashtag = $this->input->post('hashtag');
		$url = "https://api.instagram.com/v1/tags/".$hashtag."/media/recent?client_id=".$this->clientid."&access_token=".$this->clientsecret."&count=300";
		$stream = $this->rpc->HTTPGet($url);
		echo $stream;
	}



}
