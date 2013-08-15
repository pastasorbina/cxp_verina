<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Config Class
 *
 * @author	William
 *
 */

class Idle  {

	public $CI;
	public $branch_id;

	function __construct() {
		$this->CI =& get_instance();
		$this->branch_id = BRANCH_ID;

		$this->assign_site_config();
	}

	function assign_site_config() {
		//$this->CI->db->where('b_id' , $this->branch_id);
		$res = $this->CI->db->get('config');
		$config = $res->result_array();
		$site_config = array();
		foreach($config as $k=>$tmp) {
			@define('site_config.'.$tmp['c_key'], $tmp['c_value']);
			$site_config[$tmp['c_key']] = $tmp['c_value'];
			//$this->CI->sci->assign($tmp['c_key'], $tmp['c_value']);
			//$this->CI->config->set_item($tmp['c_key'], $tmp['c_value']);
			//define('site_config', $$config);
		}
		//print_r($site_config);

		$this->CI->sci->assign('site_config', $site_config);
		$this->CI->config->set_item('site_config', $site_config);
		return $config;
	}

}

?>
