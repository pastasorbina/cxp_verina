<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Media Class
 * media library
 *
 * @author	William
 *
 */

class Media  {

	public $CI;

	function __construct() {
		$this->CI =& get_instance();

	}

	function get_branch_id() {
		return $this->branch_id;
	}

	function get_branch() {
		return $this->branch;
	}

	function insert_media( $mod='', $foreign_id=0, $action='update' ) {

		//insert/update media
		$m_id = $this->CI->input->post('m_id');
		$mr_id = $this->CI->input->post('mr_id');
		$mr_pos = $this->CI->input->post('mr_pos');
		//TODO:insert validation here

		//print_r( $m_id);
		//print_r($mr_id);
		//print_r($mr_pos);

		foreach($mr_pos as $k=>$tmp) {
			//get current media related to this item
			$result = array();
			$this->CI->db->where('mr_id' , $mr_id[$k]);
				$this->CI->db->order_by('mr_stamp' , 'DESC');
				$res = $this->CI->db->get('media_relation');
				$result = $res->row_array();


				if(isset($mr_id[$k])) {
					$this->CI->db->where('mr_id' , $mr_id[$k]);
					$this->CI->db->set('mr_status' , 'Deleted');
					$this->CI->db->update('media_relation');
				}

				if($m_id[$k]) { 
					$this->CI->db->set('m_id' , $m_id[$k] );
					$this->CI->db->set('mr_pos' , $mr_pos[$k] );
					$this->CI->db->set('mr_foreign_id' , $foreign_id );
					$this->CI->db->set('mr_module' , $mod );
					$this->CI->db->set('b_id' , $this->CI->branch_id);
					$this->CI->db->set('mr_status' , 'Active');
					if( $result ) {
						$this->CI->db->where('mr_id' , $mr_id[$k] );
						$this->CI->db->update('media_relation');
					} else {
						$this->CI->db->set('mr_entry' , date('Y-m-d H:i:s') );
						$this->CI->db->insert('media_relation');
					}
				}
		}
	}

}
?>
