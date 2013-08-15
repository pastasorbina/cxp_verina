<?php

class Mod_brand extends MY_Model {

	//var $table_name = 'brand';
	//var $id_field = 'br_id';
	//var $entry_field = 'br_entry';
	//var $stamp_field = 'br_stamp';
	//var $status_field = 'br_status';
	//var $deletion_field = 'br_deletion';
	//var $order_by = '';
	//var $order_dir = '';

    function __construct() {
        parent::__construct();
    }
	
	function get_all_brand(){
		$this->db->where('br_status' , 'Active');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		return $brands;
	}

	function get_promo_by_id($pr_id=0) {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->where('pr_id' , $pr_id);
		$res = $this->db->get('promo');
		$promo = $res->row_array();
		return $promo;
	}

	function get_promo_onsale($limit=0) {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->where('pr_status' , 'Active');
		$this->db->where('pr_start_promo <=' , 'NOW()', FALSE );
		$this->db->where('pr_end_promo >' , 'NOW()', FALSE );
		$this->db->order_by('pr_end_promo' , 'ASC');
		if($limit !=0) {
			$this->db->limit($limit);
		}
		$res = $this->db->get('promo');
		$data = $res->result_array(); 
		foreach($data as $k=>$tmp) {
			$diff = get_time_difference(date('Y-m-d H:i:s'), $tmp['pr_end_promo']);
			$data[$k]['time_diff'] = $diff;
		}
		return $data;
	}
	
	function get_brand_onsale_list($limit=0) {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->where('pr_status' , 'Active');
		$this->db->where('pr_start_promo <=' , 'NOW()', FALSE );
		$this->db->where('pr_end_promo >' , 'NOW()', FALSE );
		$this->db->order_by('pr_end_promo' , 'ASC');
		if($limit !=0) {
			$this->db->limit($limit);
		}
		$this->db->select('pr_br_id');
		$res = $this->db->get('promo');
		$datat = $res->result_array(); 
		$data = array();
		foreach($datat as $k=>$tmp) {
			$data[$k] = $tmp['pr_br_id'];
		}
		return $data;
	}
	
	function get_promo_onsale_list($limit=0) {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->where('pr_status' , 'Active');
		$this->db->where('pr_start_promo <=' , 'NOW()', FALSE );
		$this->db->where('pr_end_promo >' , 'NOW()', FALSE );
		$this->db->order_by('pr_end_promo' , 'ASC');
		if($limit !=0) {
			$this->db->limit($limit);
		}
		$this->db->select('pr_id');
		$res = $this->db->get('promo');
		$datat = $res->result_array(); 
		$data = array();
		foreach($datat as $k=>$tmp) {
			$data[$k] = $tmp['pr_id'];
		}
		return $data;
	}

	function get_promo_comingsoon($limit=0) {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->where('pr_status' , 'Active');
		$this->db->where('pr_start_promo >' , date('Y-m-d H:i:s') );
		$this->db->order_by('pr_start_promo' , 'ASC');
		if($limit !=0) {
			$this->db->limit($limit);
		}
		$res = $this->db->get('promo');
		$data = $res->result_array();
		foreach($data as $k=>$tmp) {
			$diff = get_time_difference( date('Y-m-d H:i:s'), $tmp['pr_start_promo']);
			$data[$k]['time_diff'] = $diff;
		}
		return $data;
	}
	
	
	function get_promo_products($pr_id=0) {
		$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
		$this->db->where('pr_id' , $pr_id);
		$res = $this->db->get('promo_detail');
		$result = $res->result_array(); 
		return $result;
	}
	
	function get_related_product($pr_id=0, $exeption_p_id=0, $pagelimit=3) { 
		$this->db->join('promo' , 'promo.pr_id = promo_detail.pr_id' , 'left');
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left'); 
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
		$this->db->where('promo.pr_id' , $pr_id);
		$this->db->where('product.p_id !=' , $exeption_p_id);
		$this->db->limit($pagelimit);
		$res = $this->db->get('promo_detail');
		$related_product = $res->result_array();
		return $related_product;
	}

}

?>
