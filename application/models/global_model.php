<?php

class Global_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

	function get_config() {
		$res = $this->db->get('config');
		$result = $res->result_array(); 
		return $result;
	}

}

/*
** EOF: global_model.php
** Last Modified: Wed Jul 20 23:14:43 2011
** NEWT CMS Engine
** Author: Go William Goszal (pastasorbina@gmail.com)
*/
