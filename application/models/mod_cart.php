<?php

class Mod_cart extends CI_Model {

	var $userinfo;

    function __construct() {
        parent::__construct();

		//get userinfo
		$this->userinfo = $this->session->get_userinfo('member');
    }

	//get number of record on current cart
	function get_cart_count() {
		$m_id = $this->userinfo['m_id'];
		$this->db->where('m_id' , $m_id);
		$this->db->where('cart_status' , 'Active');
		$count = $this->db->count_all_results('cart');
		return $count;
	}

}

?>
