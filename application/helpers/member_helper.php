<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * generate registration key by email
 */
function generate_registration_key($email='') {
	$uniq = uniqid();
	$hash = md5($uniq.$email);
	return $hash;
}
function generate_activation_key($email='') {
	$uniq = uniqid();
	$hash = md5($uniq.'activation'.$email);
	return $hash;
}


function get_member_by_registration_key($registration_key='') {
	if($registration_key == '') return FALSE;
	$CI =& get_instance();
	$CI->db->where('m_registration_key' , $registration_key);
	$res = $CI->db->get('member');
	$member = $res->row_array();
	if(!$member){ return FALSE; }
	return $member;
}



function check_old_password($str) {
	if($registration_key == '') return FALSE;
	$CI =& get_instance();
	$CI->db->where('m_id' , $CI->userinfo['m_id'] );
	$res = $CI->db->get('member');
	$data = $res->row_array();
	$salt = $CI->config->item('salt');
	$password = md5($salt.$str);
	if (!$data || $data['m_pass'] != $password) {
		$CI->form_validation->set_message('check_old_password', 'Old password doesn\'t match !');
		return FALSE;
	} else {
		return TRUE;
	}
}

?>
