<?

class Mod_transaction extends MY_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
		$this->userinfo = $this->session->get_userinfo();
    }

	function status_change_history($from='', $to='') {
		$this->db->set('tsh_from' , $from);
		$this->db->set('tsh_to' , $to);
		$this->db->set('u_id' , $this->userinfo['u_id']);
		$this->db->set('tsh_entry' , "NOW()", FALSE);
		$this->db->insert('transaction_status_history');
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}

	function get_all_province() {
		$this->db->where('ap_status' , 'Active');
		$this->db->order_by('ap_name' , 'asc');
		$res = $this->db->get('area_province');
		$result = $res->result_array();
		return($result);
	}

	function get_all_city_by_province($ap_id=0) {
		$this->db->where('ap_id' , $ap_id);
		$this->db->where('ac_status' , 'Active');
		$this->db->order_by('ac_name' , 'asc');
		$res = $this->db->get('area_city');
		$result = $res->result_array();
		return($result);
	}

	function get_transaction_by_id($trans_id=0 ) {
		
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();
		if(!$trans){ return false; }

		//get detail
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction_detail');
		$trans['detail'] = $res->result_array();

		return $trans;
	}


}

?>
