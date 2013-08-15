<?php

class Mod_media extends MY_Model {

  var $table_name = 'media';
  var $_table_name = 'media';
  var $id_field = 'm_id';
  var $entry_field = 'm_entry';
  var $stamp_field = 'm_stamp';
  var $status_field = 'm_status';
  var $deletion_field = 'm_deletion';
  var $order_by = 'm_entry';
  var $order_dir = 'DESC';

  function __construct(){
	  parent::__construct();
  }

  function get_media( $module='', $position='', $foreign_id=0 ) {
	  $this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
	  $this->db->where('mr_status' , 'Active' );
	  $this->db->where('mr_foreign_id' , $foreign_id);
	  $this->db->where('media.b_id' , $this->branch_id);
	  $this->db->where('mr_module' , $module);
	  $this->db->where('mr_pos' , $position);
	  $res = $this->db->get('media_relation');
	  $result = $res->row_array();
	  return $result;
  }


}

?>
