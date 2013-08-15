<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Branch Class
 * manage branching for multi site
 *
 * @author	William
 *
 */

class Branch  {

	public $CI;
	public $branch_code = '';
	public $branch_id = 0;
	
	public $branch = array();

	function Branch() {
		$this->CI =& get_instance();

		$this->branch_id = BRANCH_ID;

		$this->CI->db->where('b_id' , $this->branch_id);
		$res = $this->CI->db->get('branch');
		$branch = $res->row_array();

		//set branch into CI Globals
		$this->CI->branch = $branch;
		$this->CI->branch_id = $branch['b_id'];

		$this->set_branch($branch);
	}

	function get_branch_id() {
		return $this->branch_id;
	}

	function set_branch( $branch = array() ) {
		$this->branch = $branch;
	}
	function get_branch() {
		return $this->branch;
	}


}
?>
