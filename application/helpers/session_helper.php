<?php

function get_bread($bread){
	//get the default object
	$CI =& get_instance();
	$CI->load->library('session');
	$data = $CI->session->get_bread($bread);
	return $data;
}

?>
