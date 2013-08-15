<?php
/**
 * Database Helper
 */


function db_get_lock($lock_name='', $wait_time=10) {
	$CI =& get_instance();
	$prefix = $CI->config->item('global_prefix');
	if(!$prefix) { $prefix = $_SERVER['HTTP_HOST']; }
	$lock_name = $prefix."_".$lock_name;

	$res = $CI->db->query("SELECT GET_LOCK('$lock_name', $wait_time) AS db_lock");
	$lock = $res->row_array();
	$lock_status = $lock['db_lock'];
	return $lock_status;
}

function db_release_lock($lock_name='') {
	$CI =& get_instance();
	$prefix = $CI->config->item('global_prefix');
	if(!$prefix) { $prefix = $_SERVER['HTTP_HOST']; }
	$lock_name = $prefix."_".$lock_name;

	$res = $CI->db->query("SELECT RELEASE_LOCK('$lock_name') AS db_lock");
	$lock = $res->row_array();
	$lock_status = $lock['db_lock'];
	return $lock_status;
}


?>
