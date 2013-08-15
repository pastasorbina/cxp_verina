<?

class Mod_global extends MY_Model {

    function __construct() {
        // Call the Model constructor
        parent::__construct();
    }

	function get_options($table_name , $key , $value , $condition = "" , $order_by = "" , $limit = 0) {
		$options = array();
		if ($order_by) $this->db->order_by($order_by);
		if ($condition) $this->db->where($condition);
		if ($limit > 0) $this->db->limit($limit);
		$res = $this->db-> get($table_name);
		foreach($res->result() as $row) {
			$options[$row->$key] = $row->$value;
		}
		return $options;
	}

	function get_mail_title($mail_body) {
		$mb_ex = explode("\n" , $mail_body);
		if ($mb_ex)	return trim($mb_ex[0]);
		else return "Puratama Email System";
	}

	function generate_lun($number) {
		$dbl = array(0 , 2 , 4 , 6 , 8 , 1 , 3 , 5 , 7 , 9);

		$sum = 0;
		$alternate = FALSE;
		$s = $number . '0';
		for ($i = strlen($s); $i >= 1 ; $i--) {
			if ($alternate) {
				$sum = $sum + $dbl[$s[$i - 1]];
			}
			else {
				$sum = $sum + $s[$i - 1];
			}
			$alternate = !$alternate;
		}

		return ((string)$number . (10 - ($sum % 10)) % 10);
	}

}

?>
