<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| REQUIRED CONFIGURATION
|--------------------------------------------------------------------------
|
| USER SET Configuration
|
*/

$config['user_set']['admin'] = array(

		'user'	=> array(
			'table_name'		=>	'user',
			'id_field'			=>	'u_id',
			'username_field'	=>	'u_login',
			'password_field'	=>	'u_pass',
			'stamp_field'		=>	'u_stamp',
			'entry_field'		=>	'u_entry',
			'status_field'		=>	'u_status',
		),

		'user_role'	=> array(
			'table_name'		=>	'user_role',
			'id_field'			=>	'ur_id',
			'stamp_field'		=>	'ur_stamp',
			'entry_field'		=>	'ur_entry',
			'status_field'		=>	'ur_status',
		),

);

$config['user_set']['main'] = array(

		'user'	=> array(
			'table_name'		=>	'member',
			'id_field'			=>	'm_id',
			'username_field'	=>	'm_login',
			'password_field'	=>	'm_pass',
			'stamp_field'		=>	'm_stamp',
			'entry_field'		=>	'm_entry',
			'status_field'		=>	'm_status',
		),

		'user_role'	=> array(
			'table_name'		=>	'user_role',
			'id_field'			=>	'ur_id',
			'stamp_field'		=>	'ur_stamp',
			'entry_field'		=>	'ur_entry',
			'status_field'		=>	'ur_status',
		),

);


?>
