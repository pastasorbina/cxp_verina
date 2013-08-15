<?php

class Mod_media_relation extends MY_Model {

  var $table_name = 'media_relation';
  var $_table_name = 'media_relation';
  var $id_field = 'mr_id';
  var $entry_field = 'mr_entry';
  var $stamp_field = 'mr_stamp';
  var $status_field = 'mr_status';
  var $order_by = 'mr_entry';
  var $order_dir = 'DESC';

	function __construct(){
		parent::__construct();
	}

	/* Get related products */
	function get_related( $p_id1 = 0 ) {
		$this->db->where('p_id1' , $p_id1);
		$this->db->order_by('prl_order' , 'asc');
		$res = $this->db->get( $this->table_name );
		$data = $res->result_array();
		foreach($data as $k => $tmp) {
			$prod = $this->product_model->get_product( $tmp['p_id2'] );
			$data[$k] = array_merge( $data[$k], $prod );
		}
		return $data;
	}

	/* Rearrange order */
	function reorder( $p_id1 = 0 ) {
		$this->db->where('p_id1' , $p_id1 );
		$this->db->order_by('prl_order' , 'asc');
		$res = $this->db->get('product_related');
		$data = $res->result_array();
		$this->db->trans_start();
		for( $i=0; $i<sizeof($data); $i++ ) {
			$this->db->set('prl_order' , $i+1);
			$this->db->where('p_id1' , $data[$i]['p_id1'] );
			$this->db->where('p_id2' , $data[$i]['p_id2'] );
			$this->db->update('product_related');
		}
		$this->db->trans_complete();
	}



	/* Move */
	function move( $dir='up', $p_id1=0, $p_id2=0 ) {
		$this->db->start_cache();
		$this->db->where('p_id1' , $p_id1);
		$this->db->stop_cache();
		$this->db->where('p_id2' , $p_id2);
		$res = $this->db->get( $this->table_name );
		$current = $res->row_array();
		$flag = FALSE;
		switch( $dir ) {
			case 'up' :
				if( $current['prl_order'] != 1 ) {
					$this->db->where('prl_order' , $current['prl_order']-1);
					$res = $this->db->get( $this->table_name );
					$other = $res->row_array();
					$flag = TRUE;
				}
				break;
			case 'down' :
				$res = $this->db->get('product_related');
				$all = $res->result_array();
				if( $current['prl_order'] < sizeof($all) ) {
					$this->db->where('prl_order' , $current['prl_order']+1);
					$res = $this->db->get( $this->table_name );
					$other = $res->row_array();
					$flag = TRUE;
				}
				break;
			default :
				break;
		}

		if($flag == TRUE) {
			//switch order
			$this->db->trans_start();
				$this->db->where('p_id2' , $current['p_id2'] );
				$this->db->set('prl_order' , $other['prl_order'] );
				$this->db->update( $this->table_name );
				$this->db->where('p_id2' , $other['p_id2'] );
				$this->db->set('prl_order' , $current['prl_order'] );
				$this->db->update( $this->table_name );
			$this->db->trans_complete();
			$this->db->flush_cache();
			return $this->db->trans_status();
		} else {
			$this->db->flush_cache();
			return FALSE;
		}
	}



}

?>
