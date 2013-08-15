<?php
	
	function build_calendar() {
		$CI =& get_instance();
		
		
		$month = date('n');
		$year = date('Y');

		$sql = "
			SELECT *,
				DAY(e_date) as dt
			FROM event e
			WHERE e_status = 'Active'
				AND MONTH(e_date) = MONTH(CURRENT_DATE())
				AND YEAR(e_date) = YEAR(CURRENT_DATE())
		";
		$res = $CI->db->query($sql);
		$data = array();
		foreach ($res->result() as $row) {
			$data[$row->dt] = site_url("event/view/$year/$month/{$row->dt}");
		}

		$prefs = array();
		$prefs['template'] = $CI->load->view('event/calendar_view.htm' , '' , true);
		$CI->load->library('calendar' , $prefs);
		echo $CI->calendar->generate($year , $month , $data);
		
	}
	

?>