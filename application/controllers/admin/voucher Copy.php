<?php

class Voucher extends MY_Controller {

	var $mod_title = 'Voucher';

	var $prefix = '7612';
	var $digit = 12;
	var $path = 'export/';
	var $perpage = 100;

	function Voucher() {
		set_time_limit(1800);
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);
	}

	function index() {
		$sql = "
			SELECT v.vs_id, vs.vs_name, v_nominal, v_status , count(v_code) as count_code
			FROM voucher v
			LEFT JOIN voucher_set vs ON vs.vs_id = v.vs_id
			GROUP BY v.vs_id, v_nominal DESC , v_status
		";
		$res = $this->db->query($sql);
		$this->sci->assign('voucher' , $res->result_array());

		// Validation class
		$this->load->library('form_validation');

		$this->form_validation->set_rules('vs_name', 'Batch Name', "required|trim");
		$this->form_validation->set_rules('v_nominal', 'Nominal', "required|trim|numeric");
		$this->form_validation->set_rules('v_count', 'Quantity', "required|trim|numeric");
		$this->form_validation->set_rules('v_prefix', 'Prefix', "trim");
		$this->form_validation->set_rules('v_start_number', 'Start Number', "numeric|trim");
		$this->form_validation->set_rules('v_digit', 'Digit', "trim|numeric");
		$this->form_validation->set_rules('v_suffix', 'Suffix', "trim");
		$this->form_validation->set_rules('v_start_date', 'Start Date', "required|trim");
		$this->form_validation->set_rules('v_end_date', 'End Date', "required|trim");

		if ($this->form_validation->run() == FALSE) {
			$this->sci->assign('validation' , $this->form_validation);
			$this->sci->da('index.htm');
		}
		else {
			//$this->useraction->admin_create('voucher/generate' , $this->form_validation->v_nominal , $this->form_validation->v_count);

			// Create Batch Voucher Set
			$this->db->set('vs_name' , $this->input->post('vs_name') );
			$this->db->set('vs_entry' , 'NOW()' , false);
			$this->db->insert('voucher_set');
			$vs_id = $this->db->insert_id();
			//$this->mod_global->save_change_history('Add' , 'voucher_set' , 'vs_id' , $vs_id);

			if ($this->input->post('v_start_number')  != '') {
				$err = $this->_generate_custom_voucher(
					$this->input->post('v_prefix') ,
					$this->input->post('v_start_number'),
					$this->input->post('v_digit'),
					$this->input->post('v_suffix') ,
					$this->input->post('v_nominal') ,
					$this->input->post('v_count') ,
					$this->input->post('v_start_date') ,
					$this->input->post('v_end_date') ,
					$vs_id
				);
				if ($err == 0) redirect('voucher/index');
			} else {
				$this->_generate_voucher(
					$this->input->post('v_nominal') ,
					$this->input->post('v_count') ,
					$this->input->post('v_start_date') ,
					$this->input->post('v_end_date') ,
					$vs_id
				);
				redirect('voucher/index');
			}
		}
	}

	function detail_jumper() {
		$page = $this->input->post('page');
		$key = $this->input->post('searchkey');
		$page_number = $this->input->post('page_number');
		$orderby = $this->input->post('orderby');
		$ascdesc = $this->input->post('ascdesc');

		$basekey = base64_encode($key);

		redirect("$page/$orderby/$ascdesc/$page_number/$basekey");
	}

	function detail($vs_id = 0 , $nominal = 0 , $status='New' , $orderby = 'v_DOT_v_id' , $ascdesc = 'ASC' , $page_number = 0 , $searchkey = '') {
		$this->sci->assign('vs_id' , $vs_id);
		$this->sci->assign('status' , $status);
		$this->sci->assign('nominal' , $nominal);
		$this->sci->assign('status' , $status);

		// Start Cache
		$this->db->start_cache();

		// Search
		$this->db->where('v.vs_id' , $vs_id);
		$this->db->where('v.v_nominal' , $nominal);
		$this->db->where('v.v_status' , $status);

		// Order By
		$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		$this->db->order_by($orderbyconv , $ascdesc);

		// Get voucher database
		//$this->db->select('v.* , br.br_name , isp.is_id');
		$this->db->select('v.*');
		$this->db->from('voucher v');
		//$this->db->join('item_sale_payment isp' , 'isp.v_id = v.v_id' , 'left');
		//$this->db->join('branch br' , 'br.br_id = isp.br_id' , 'left');
		$this->db->stop_cache();

		// Pagination
		$row_count = $this->db->count_all_results();
		$total_page = ceil($row_count / $this->perpage);
		$page_number = min($total_page , $page_number);
		$page_number = max(1 , $page_number);
		$this->sci->assign('page_number' , $page_number);
		$this->sci->assign('total_page' , $total_page);
		$this->sci->assign('perpage' , $this->perpage);
		$this->sci->assign('row_count' , $row_count);
		$this->db->limit($this->perpage , $this->perpage * ($page_number - 1 ));

		$res = $this->db->get();
		$this->sci->assign('maindata' , $res->result_array());

		// Flush the cache
		$this->db->flush_cache();

		$this->sci->da('voucher/detail.htm');
	}

	function search() {
		$v_code = $this->input->post('v_code');
		$res = $this->db->
			like('v_code' , "$v_code")->
			//join('item_sale_payment isp' , 'isp.v_id = v.v_id' , 'left')->
			//join('branch br' , 'br.br_id = isp.br_id' , 'left')->
			order_by('v_code' , 'ASC')->
			get('voucher v');
		$this->sci->assign('voucher' , $res->result_array());

		$this->sci->da('search.htm');
	}

	function download_csv() {
		// Load libraries
		$this->load->dbutil();
		$this->load->helper('download');

		$res = $this->db->
			select('v_code as `Voucher Code` , v_nominal as `Voucher Nominal` , v_status as `Status`')->
			orderby('v_code' , 'asc')->
			get('voucher');
		$data = $this->dbutil->csv_from_result($res);

		$name = 'voucher.csv';

		force_download($name, $data);
	}

	function download_xml() {
		// Load libraries
		$this->load->dbutil();
		$this->load->helper('download');

		$res = $this->db->
			select('v_code as `voucher_code` , v_nominal as `voucher_nonimal` , v_status as `voucher_status`')->
			orderby('v_code' , 'asc')->
			get('voucher');
		$data = $this->dbutil->xml_from_result($res);

		$name = 'voucher.xml';

		force_download($name, $data);
	}

	function _generate_custom_voucher($prefix , $start , $digit , $suffix , $nominal , $count , $start_date , $end_date , $vs_id) {
		$start = (int) $start;

		//// Open for CSV write
		//$filename = $this->path. "voucher" . date("Ymd_His") . ".csv";
		//$fp = fopen($filename, 'w');
		//if (!$fp) {
		//	echo "Cannot write to file";
		//	return;
		//}
		//// Generate header
		//fputcsv($fp, array('voucher_code' , 'voucher_nominal'));

		$error = 0;
		$sql_parts = array();
		for($a = 0 ; $a < $count; $a++) {
			$next_code = $start + $a;
			$real_code = $prefix . sprintf("%0{$digit}s" , $next_code) . $suffix;

			// Put into CSV
			//fputcsv($fp, array($real_code , $nominal));

			// Check the database

			$res = $this->db->
				where('v_code' , $real_code)->
				get('voucher');
			if ($res->num_rows() > 0) {
				echo "Voucher number $real_code has been used <br />";
				$error = 1;
			}
			else {
				//$sql_parts[] = "('$real_code' , '$nominal' , '$start_date' , '$end_date')";
				$this->db->set('vs_id' , $vs_id);
				$this->db->set('v_code' , $real_code);
				$this->db->set('v_nominal' , $nominal);
				$this->db->set('v_start_date' , $start_date);
				$this->db->set('v_end_date' , $end_date);
				$this->db->insert('voucher');
				$ii = $this->db->insert_id();
				$this->mod_global->save_change_history('Add' , 'voucher' , 'v_id' , $ii);
			}

		}
		//$this->mod_global->insert_multiple_record($sql_parts , 'voucher' , 'v_code , v_nominal , v_start_date , v_end_date' , 'v_id');

		//fclose($fp);

		return $error;
	}

	function _generate_voucher($nominal , $count , $start_date , $end_date , $vs_id) {
		// Get latest voucher
		$res = $this->db->
			like('v_code' , $this->prefix . "%")->
			order_by('v_code' , 'desc')->
			limit(1)->
			get('voucher');
		if ($res->num_rows() > 0) {
			$code = $res->row()->v_code;
			$next_code = substr($code , strlen($this->prefix) , strlen($code) - strlen($this->prefix) - 1);
		}
		else {
			$next_code = str_repeat("0" , $this->digit - strlen($this->prefix) - 2) . "0";
		}

		//// Open for CSV write
		//$filename = $this->path. "voucher" . date("Ymd_His") . ".csv";
		//$fp = fopen($filename, 'w');
		//if (!$fp) {
		//	echo "Cannot write to file";
		//	return;
		//}
		//// Generate header
		//fputcsv($fp, array('voucher_code' , 'voucher_nominal'));

		$sql_parts = array();
		for ($a = 0 ; $a < $count ; $a++) {
			$sum_digites = $this->digit - strlen($this->prefix) - 1;
			$next_code = sprintf("%0{$sum_digites}s" , $next_code + 1);
			$real_code = $this->_generate_lun($this->prefix . $next_code);

			// Put into CSV
			//fputcsv($fp, array($real_code , $nominal));

			//$sql_parts[] = "('$real_code' , '$nominal' , '$start_date' , '$end_date')";
			$this->db->set('vs_id' , $vs_id);
			$this->db->set('v_code' , $real_code);
			$this->db->set('v_nominal' , $nominal);
			$this->db->set('v_start_date' , $start_date);
			$this->db->set('v_end_date' , $end_date);
			$this->db->insert('voucher');
			$ii = $this->db->insert_id();
			//$this->mod_global->save_change_history('Add' , 'voucher' , 'v_id' , $ii);
		}

		//$this->mod_global->insert_multiple_record($sql_parts , 'voucher' , 'v_code , v_nominal , v_start_date , v_end_date' , 'v_id');

		//fclose($fp);
	}

	function delete($v_id = 0) {
		// Delete data
		$this->db->where('v_id' , $v_id);
		$this->db->delete('voucher');

		$this->useraction->admin_delete('voucher/delete' , $v_id);
		$this->mod_global->save_last_query($this->db->last_query() , 'Delete' , 'voucher' , 'v_id' , $v_id);

		redirect('voucher/index');
	}

	function edit($v_id = 0) {
		// Get user information
		$this->db->where('v_id' , $v_id);
		$res = $this->db->get('voucher');
		if ($row = $res->row()) {
			$this->sci->assign('voucher' , $row);
			$voucher = $row;
		}
		else {
			redirect('voucher/index');
		}

		// Define V_status
		$v_status = array(
			'Used' => 'Used',
			'New' => 'New'
		);
		$this->sci->assign("v_status" , $v_status);

		// Validation class
		$this->load->library('validation');

		$rules['v_code'] = "trim|required";
		$rules['v_nominal'] = "trim|required|numeric";
		$rules['v_start_date'] = "trim|required";
		$rules['v_end_date'] = "trim|required";
		$rules['v_status'] = "trim|required";
		$this->form_validation->set_rules($rules);

		$fields['v_code']	= 'Voucher code';
		$fields['v_nominal']	= 'Nominal';
		$fields['v_start_date']	= 'Start';
		$fields['v_end_date']	= 'End';
		$fields['v_status']	= 'Status';
		$this->form_validation->set_fields($fields);

		// Set default variable
		$this->form_validation->v_code = $voucher->v_code;
		$this->form_validation->v_nominal = $voucher->v_nominal;
		$this->form_validation->v_start_date = $voucher->v_start_date;
		$this->form_validation->v_end_date = $voucher->v_end_date;
		$this->form_validation->v_status = $voucher->v_status;

		if ($this->form_validation->run() == FALSE) {
			$this->sci->assign('validation' , $this->form_validation);
			$this->sci->da('voucher/edit.htm');
		}
		else {
			$this->db->set('v_code' , $this->form_validation->v_code);
			$this->db->set('v_nominal' , $this->form_validation->v_nominal);
			$this->db->set('v_start_date' , $this->form_validation->v_start_date);
			$this->db->set('v_end_date' , $this->form_validation->v_end_date);
			$this->db->set('v_status' , $this->form_validation->v_status);
			$this->db->where('v_id' , $v_id);
			$this->db->update('voucher');

			$this->useraction->admin_edit('voucher/edit' , $v_id , $this->form_validation->v_code);

			$this->mod_global->save_change_history('Update' , 'voucher' , 'v_id' , $v_id);

			redirect('voucher/index');
		}
	}

	function _generate_lun($number) {
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

	function import() {
		$this->sci->da('voucher/import.htm');
	}

	function import_do() {
		$error = 0;

		// Create new batch
		$vs_name = $this->input->post('vs_name');
		$this->db->set('vs_name' , $vs_name);
		$this->db->set('vs_entry' , 'NOW()' , false);
		$this->db->insert('voucher_set');
		$vs_id = $this->db->insert_id();
		$this->mod_global->save_change_history('Add' , 'voucher_set' , 'vs_id' , $vs_id);

		$data = $this->input->post('data');
		$data_ex = explode("\n" , $data);
		$sql_parts = array();
		foreach($data_ex as $de) {
			if ($de == '') continue;
			list($nominal , $code, $start_date , $end_date) = explode("\t" , $de);
			$res = $this->db->
				where('v_code' , $code)->
				get('voucher');
			if ($res->num_rows() > 0) {
				echo "Voucher number $code has been used <br />";
				$error = 1;
			}
			else {
				$sql_parts[] = "('$vs_id' , '$code' , '$nominal' , '$start_date' , '$end_date')";
			}
		}
		$this->mod_global->insert_multiple_record($sql_parts , 'voucher' , 'vs_id , v_code , v_nominal , v_start_date , v_end_date' , 'v_id');

		if ($error == 0) redirect('voucher/import');
	}

	function delete_all($vs_id , $v_status) {
		$this->db->where('vs_id' , $vs_id);
		$this->db->where('v_status' , $v_status);
		$this->db->delete('voucher');

		$this->mod_global->save_last_query($this->db->last_query() , 'Delete' , 'voucher' , 'vs_id' , $vs_id);

		redirect('voucher');
	}

	function change_expire($vs_id , $v_status) {
		$this->sci->assign('vs_id' , $vs_id);
		$this->sci->assign('v_status' , $v_status);
		$this->sci->da('voucher/change_expire.htm');
	}

	function change_expire_do() {
		$vs_id = $this->input->post('vs_id');
		$v_status = $this->input->post('v_status');
		$v_start_date = $this->input->post('v_start_date');
		$v_end_date = $this->input->post('v_end_date');

		$this->db->where('vs_id' , $vs_id);
		$this->db->where('v_status' , $v_status);
		$this->db->set('v_start_date' , $v_start_date);
		$this->db->set('v_end_date' , $v_end_date);
		$this->db->update('voucher');

		//$this->mod_global->save_last_query($this->db->last_query() , 'Update' , 'voucher' , 'vs_id' , $vs_id);

		redirect("admin/voucher");
	}

}
