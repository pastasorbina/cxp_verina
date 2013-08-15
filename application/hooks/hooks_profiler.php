<?php

class Hooks_profiler {

	function __construct() {
		//parent::__construct();
		$this->CI =& get_instance();
		$this->CI->load->library('sci');
	}


	function run(){
		if($this->CI->sci->get_display_type() != 'plain') {
			$this->CI->output->enable_profiler($this->CI->config->item('debug_mode'));
		}
	}

}
