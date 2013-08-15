<?php
class Shipping_method extends MY_Controller {

	var $mod_title = 'Manage Shipping Method';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'shipping_method';
	var $id_field = 'sm_id';
	var $status_field = 'sm_status';
	var $entry_field = 'sm_entry';
	var $stamsm_field = 'sm_stamp';
	var $deletion_field = 'sm_deletion';
	var $order_field = 'sm_entry';

	var $author_field = 'sm_author';
	var $editor_field = 'sm_editor';

	var $search_in = array('sm_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('SHIPPING_MANAGE_METHOD'), 'admin');

		$this->sci->assign('use_ajax' , FALSE);


	}

	function enum_setting($maindata=array()) {

		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('sm_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('sm_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('sm_insurance_tax', 'Insurance tax', 'trim|xss_clean');
		$this->form_validation->set_rules('sm_url', 'Website URL', 'trim|xss_clean');
	}

	function database_setter() {
		$this->db->set('sm_name' , $this->input->post('sm_name') );
		$this->db->set('sm_code' , $this->input->post('sm_code') );
		$this->db->set('sm_insurance_tax' , $this->input->post('sm_insurance_tax') );
		$this->db->set('sm_url' , $this->input->post('sm_url') );
	}


	function pre_add_edit() {  }
	function pre_add() { }
	function pre_edit($id=0) { }

	function delete($id=0) {
		$this->change_status($id, 'Deleted');
	}


	function manage($sm_id=0, $ap_id=0 ) {
		$this->sci->assign('sm_id' , $sm_id);
		$this->sci->assign('ap_id' , $ap_id);

		//get all method
		$this->db->where('sm_status' , 'Active');
		$res = $this->db->get('shipping_method');
		$all_shipping_method = $res->result_array();
		$this->sci->assign('all_shipping_method' , $all_shipping_method);

		//get this method
		$this->db->where('sm_id' , $sm_id);
		$res = $this->db->get('shipping_method');
		$shipping_method = $res->row_array();
		$this->sci->assign('shipping_method' , $shipping_method);

		//get all province
		$this->db->where('ap_status' , 'Active');
		$this->db->order_by('ap_name' , 'asc');
		$res = $this->db->get('area_province');
		$province = $res->result_array();
		$this->sci->assign('province' , $province);

		//get all shipping price in this method
		$this->db->join('area_province ap' , 'ap.ap_id = sp.ap_id' , 'left');
		$this->db->join('area_city ac' , 'ac.ac_id = sp.ac_id' , 'left');
		$this->db->join('shipping_method sm' , 'sm.sm_id = sp.sm_id' , 'left');
		$this->db->where('sp.sm_id' , $sm_id);
		$this->db->where('sp.ap_id' , $ap_id);
		$this->db->where('sp_status' , 'Active');
		$this->db->order_by('sp_city' , 'asc');
		$this->db->from('shipping_price sp');
		$res = $this->db->get();
		$shipping_price = $res->result_array();
		$this->sci->assign('shipping_price' , $shipping_price);


		$this->load->library('form_validation');
		$this->form_validation->set_rules('sp_id[]', 'SP id', 'required|trim|xss_clean');
		$this->form_validation->set_rules('sp_price[]', 'Price', 'required|trim|xss_clean');
		$this->form_validation->set_rules('sp_disabled[]', 'Disabled', 'trim|xss_clean');
		$this->form_validation->set_rules('sp_is_fixed_price[]', 'Is Fixed Price', 'trim|xss_clean');
		$this->form_validation->set_rules('sp_code[]', 'Code', 'trim|xss_clean');

		if($this->form_validation->run() == FALSE) {
			$this->sci->da('manage.htm');
		} else {
			$this->db->trans_start();
				$userinfo = $this->session->get_userinfo();

				//then, re-insert new shipping price
				$sp_id_arr = $this->input->post('sp_id');
				$sp_price_arr = $this->input->post('sp_price');
				$sp_disabled_arr = $this->input->post('sp_disabled');
				$sp_is_fixed_price_arr = $this->input->post('sp_is_fixed_price');
				$sp_code_arr = $this->input->post('sp_code');

				foreach( $sp_id_arr as $k=>$tmp) {
					if($sp_price_arr[$tmp]!='' && $sp_code_arr[$tmp]!='') {
						$this->db->where('sp_id' , $tmp);
						$this->db->set('sm_id' , $sm_id);
						$this->db->set('ap_id' , $ap_id);
						//$this->db->set('sp_weight' , 1);
						$this->db->set('sp_price' , $sp_price_arr[$tmp] );
						$this->db->set('sp_code' , $sp_code_arr[$tmp] );
						$sp_disabled = (@$sp_disabled_arr[$tmp]=='Yes') ? 'Yes':'No';
						$this->db->set('sp_disabled' , $sp_disabled);
						$sp_is_fixed_price = (@$sp_is_fixed_price_arr[$tmp]=='Yes') ? 'Yes':'No';
						$this->db->set('sp_is_fixed_price' , $sp_is_fixed_price);
						$this->db->set('sp_editor' , $userinfo['u_id']);
						$this->db->update('shipping_price');
					}
				}
			$ok = $this->db->trans_complete();
			redirect($this->mod_url."manage/$sm_id/$ap_id");
		}
	}

	function import($sm_id = 0) {
		$this->sci->assign('sm_id' , $sm_id);
		$this->db->where('sm_id' , $sm_id);
		$res = $this->db->get('shipping_method');
		$shipping_method = $res->row_array();
		$this->sci->assign('shipping_method' , $shipping_method);

		$this->load->library('form_validation');

		$this->sci->da('import.htm');
	}

	function import_do() {
		$sm_id = $this->input->post('sm_id');
		$data = $this->input->post('data');
		$this->sci->assign('sm_id' , $sm_id);

		//explode data
		$data_ex = explode("\n" , $data);
		$sql_part = array();
		$a = -1; $ap_name = ''; $ap_id = 0;

		//iterate through data
		while($a < (sizeof($data_ex)-2) ) {
			$a++;
			$nodata = false;
			$just_ap_name = false;
			$data_ex2 = explode("\t" , $data_ex[$a] );

			//check if line contain no record
			foreach($data_ex2 as $t1) { if(!$t1 OR $t1==''){ $nodata=true;  continue; } }

			//check if this line is province name, insert/update and continue
			foreach($data_ex2 as $t1) {
				if($data_ex2[0]==''&&$data_ex2[1]!=''&&$data_ex2[2]==''&&$data_ex2[3]==''){
					$ap_name = $data_ex2[1];
					$just_ap_name = true;

					//check if already available
					$this->db->where('ap_name' , $ap_name);
					$this->db->where('ap_status' , 'Active');
					$res = $this->db->get('area_province');
					if($result = $res->row_array()) {
						$ap_id = $result['ap_id'];
					} else {
						$this->db->set('ap_name' , $ap_name);
						$this->db->set('ap_entry' , 'NOW()', false);
						$this->db->insert('area_province');
						$ap_id = $this->db->insert_id();
					}
				}
			}

			if(!$nodata && !$just_ap_name) {
				//print 'x';
				//print $a."-"; print $ap_name;
				//print_r($data_ex2);
				//print "<br>";
				list($ac_name, $ac_code, $sp_price, $sp_etd) = $data_ex2;
				//print $ac_name;
				//print "<br>";

				//remove (,) and (.) from price
				$sp_price = str_replace(',','',$sp_price);
				$sp_price = str_replace('.','',$sp_price);

				//check if city already available, if yes-update, if no-insert
				$this->db->where('ac_name' , $ac_name);
				$this->db->where('ap_id' , $ap_id);
				$this->db->where('ac_status' , 'Active');
				$res = $this->db->get('area_city');
				if($result = $res->row_array()) {
					$ac_id = $result['ac_id'];
					$this->db->where('ac_id' , $ac_id);
					$this->db->set('ac_name' , $ac_name);
					$this->db->set('ap_id' , $ap_id);
					$this->db->set('ac_code' , $ac_code);
					$this->db->update('area_city');
				} else {
					$this->db->set('ac_code' , $ac_code);
					$this->db->set('ac_name' , $ac_name);
					$this->db->set('ap_id' , $ap_id);
					$this->db->set('ac_entry' , 'NOW()', false);
					$this->db->insert('area_city');
					$ac_id = $this->db->insert_id();
				}

				//check if price already available, if yes-update, if no-insert
				$this->db->where('ac_id' , $ac_id);
				$this->db->where('sm_id' , $sm_id);
				$this->db->where('sp_status' , 'Active');
				$res = $this->db->get('shipping_price');
				$this->db->start_cache();
					$this->db->set('ac_id' , $ac_id);
					$this->db->set('sm_id' , $sm_id);
					$this->db->set('ap_id' , $ap_id);
					$this->db->set('sp_code' , $ac_code);
					$this->db->set('sp_price' , $sp_price);
					$this->db->set('sp_etd' , $sp_etd);
				$this->db->stop_cache();
				if($result = $res->row_array()) {
					$sp_id = $result['sp_id'];
					$this->db->where('sp_id' , $sp_id);
					$this->db->update('shipping_price');
				} else {
					$this->db->set('sp_entry' , 'NOW()', false);
					$this->db->insert('shipping_price');
				}
				$this->db->flush_cache();
			}
		}
		$this->sci->da('import_complete.htm');

	}

	//function manage($sm_id=0, $ap_id=0 ) {
	//	$this->sci->assign('sm_id' , $sm_id);
	//	$this->sci->assign('ap_id' , $ap_id);
	//
	//	//get this method
	//	$this->db->where('sm_id' , $sm_id);
	//	$res = $this->db->get('shipping_method');
	//	$shipping_method = $res->row_array();
	//	$this->sci->assign('shipping_method' , $shipping_method);
	//
	//	//get all province
	//	$this->db->where('ap_status' , 'Active');
	//	$res = $this->db->get('area_province');
	//	$province = $res->result_array();
	//	$this->sci->assign('province' , $province);
	//
	//	//get all area
	//	//$this->db->join('shipping_price sp' , 'sp.sa_id = shipping_area.sa_id AND sp.sm_id = '.$sm_id.'' , 'left');
	//	$this->db->where('ap_id' , $ap_id);
	//	$this->db->where('ac_status' , 'Active');
	//	//$this->db->limit(50);
	//	$res = $this->db->get('area_city');
	//	$area = $res->result_array();
	//	//get price for that area
	//	foreach( $area as $k=>$tmp ){
	//		$this->db->where('ac_id' , $tmp['ac_id']);
	//		$this->db->where('sm_id' , $sm_id);
	//		$res = $this->db->get('shipping_price');
	//		$ship_price = $res->row_array();
	//		$area[$k]['sp'] = $ship_price;
	//	}
	//	$this->sci->assign('area' , $area);
	//
	//
	//	$this->load->library('form_validation');
	//	$this->form_validation->set_rules('sp_id[]', 'SP id', 'required|trim|xss_clean');
	//	$this->form_validation->set_rules('ac_id[]', 'area id', 'required|trim|xss_clean');
	//	$this->form_validation->set_rules('sp_price[]', 'Price', 'required|trim|xss_clean');
	//	$this->form_validation->set_rules('sp_disabled[]', 'Disabled', 'trim|xss_clean');
	//	$this->form_validation->set_rules('sp_code[]', 'Code', 'trim|xss_clean');
	//
	//	if($this->form_validation->run() == FALSE) {
	//		$this->sci->da('manage.htm');
	//	} else {
	//		$this->db->trans_start();
	//			$userinfo = $this->session->get_userinfo();
	//
	//			//remove shipping price
	//			$this->db->where('sm_id' , $sm_id);
	//			$this->db->delete('shipping_price');
	//
	//			//then, re-insert new shipping price
	//			$ac_id_arr = $this->input->post('ac_id');
	//			$sp_price_arr = $this->input->post('sp_price');
	//			$sp_disabled_arr = $this->input->post('sp_disabled');
	//			$sp_code_arr = $this->input->post('sp_code');
	//
	//			//print_r($ac_id_arr);
	//			//print_r($sp_price_arr);
	//			//print_r($sp_disabled_arr);
	//			//exit();
	//			//print_r($sp_disabled_arr); exit();
	//			foreach( $ac_id_arr as $k=>$tmp) {
	//				if($sp_price_arr[$k]!='' && $sp_code_arr[$k]!='') {
	//					$this->db->set('sm_id' , $sm_id);
	//					$this->db->set('ac_id' , $tmp);
	//					$this->db->set('sp_weight' , 1);
	//					$this->db->set('sp_price' , $sp_price_arr[$k] );
	//					$this->db->set('sp_code' , $sp_code[$k] );
	//					$sp_disabled = ($sp_disabled_arr[$k]=='Yes') ? 'Yes':'No';
	//					$this->db->set('sp_disabled' , $sp_disabled);
	//					$this->db->set('sp_author' , $userinfo['u_id']);
	//					$this->db->set('sp_entry' , date('Y-m-d H:i:s') );
	//					$this->db->insert('shipping_price');
	//				}
	//			}
	//		$ok = $this->db->trans_complete();
	//		redirect($this->mod_url."manage/$sm_id");
	//	}
	//}




}
