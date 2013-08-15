<?

class Mod_area extends MY_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
    }

	function get_all() {
		$this->db->where('ap_status' , 'Active');
		$this->db->order_by('ap_name' , 'asc');
		$res = $this->db->get('area_province');
		$result = $res->result_array();
		foreach($result as $k=>$tmp) {
			$this->db->where('ap_id' , $tmp['ap_id']);
			$this->db->where('ac_status' , 'Active');
			$this->db->order_by('ac_name' , 'asc');
			$res = $this->db->get('area_city');
			$result[$k]['city'] = $res->result_array();
		}
		return $result;
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


}

?>
