<?php
class Report_transaction extends MY_Controller {

	var $mod_title = 'Transaction Report';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('REPORT_VIEW'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		//$this->load_topbar();
	}

	function load_topbar() {
		$topbar = $this->sci->fetch('admin/report_transaction/topbar.htm');
		$this->sci->assign('topbar' , $topbar);
	}

	function index(){
		$this->monthly_graph();
	}

	function total_result() {

		$this->sci->da('index.htm');
	}
	
	function monthly_graph($start_year=0, $start_month=0, $end_year=0, $end_month=0) {
		
		$this->sci->assign('section' , 'monthly_graph');
		$this->load_topbar();
		$this->sci->assign('start_year' , $start_year);
		$this->sci->assign('start_month' , $start_month);
		$this->sci->assign('end_year' , $end_year);
		$this->sci->assign('end_month' , $end_month);

		$month = date_make_selection_month();
		$this->sci->assign('month' , $month);
		$year = date_make_selection_year();
		$this->sci->assign('year' , $year);

		if($start_month != 0 AND $start_year!=0 AND $end_month!=0 AND $end_year!=0) {
			$start = "$start_year-$start_month-01";
			$end = "$end_year-$end_month-01";

			if($start <= $end) {
				$step = array();

				$d_start    = new DateTime($start);
				$d_end      = new DateTime($end);
				$interval = $d_start->diff($d_end);
				$year_diff = $interval->format('%y');
				$month_diff = $interval->format('%m');
				$diff_month = ($year_diff*12)+$month_diff;

				$a=0;
				$date = $start;
				for($i=0; $i<=$diff_month; $i++) {
					$step[$i]['year'] = date ("Y", strtotime($date));
					$step[$i]['month'] = date ("m", strtotime($date));
					$date = date ("Y-m-d", strtotime("+1 month", strtotime($date)));
				}

				$result = $step;

				foreach($step as $k=>$tmp) {
					$year = $tmp['year'];
					$month = $tmp['month'];
					$query = "SELECT *, MONTH(trans_entry) as date_month, YEAR(trans_entry) as date_year FROM transaction WHERE trans_status = 'Active'
					AND YEAR(trans_entry) = $year AND MONTH(trans_entry) = $month
					";
					$res = $this->db->query($query);
					$count = $res->num_rows();
					$result[$k]['count'] = $count;
					$result[$k]['month_string'] = date_string_month($month, TRUE);
					$result[$k]['year_string'] = $year;
				}

				$this->sci->assign('result' , $result);
			}

		}

		$this->sci->da('monthly.htm');
	}

	
	
	
	function monthly($from_year=0, $from_month=0, $to_year=0, $to_month=0, $br_id=0, $trans_payment_status='Delivered') {
		$this->sci->assign('section' , 'monthly');
		$this->load_topbar();
		
		$this->sci->assign('from_year' , $from_year);
		$this->sci->assign('from_month' , $from_month);
		$this->sci->assign('to_year' , $to_year);
		$this->sci->assign('to_month' , $to_month);
		$this->sci->assign('br_id' , $br_id);
		$this->sci->assign('trans_payment_status' , $trans_payment_status);

		$month = date_make_selection_month();
		$this->sci->assign('month' , $month);
		$year = date_make_selection_year();
		$this->sci->assign('year' , $year);
		
		//get brands
		$this->db->where('br_status' , 'Active');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		if($from_month != 0 AND $from_year!=0 AND $to_month!=0 AND $to_year!=0) {
			$from = "$from_year-$from_month-01";
			$to = "$to_year-$to_month-01";

			if($from <= $to) {  
				
				$from_temp = $from_year."-".$from_month."-01";
				$from = date ("Y-m-d H:i:s", strtotime($from_temp));
				$to_temp = $to_year."-".$to_month."-01";
				$to = date ("Y-m-d H:i:s", strtotime("+1 month", strtotime($to_temp)));
				
				$this->db->start_cache();
				$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left'); 
				$this->db->join('promo' , 'promo.pr_id = transaction.pr_id' , 'left');
				$this->db->join('brand' , 'brand.br_id = transaction.br_id' , 'left');
				$this->db->where('trans_status' , 'Active');
				$this->db->where('trans_payment_status' , 'Delivered');
				$this->db->where('trans_entry >=' , $from);
				$this->db->where('trans_entry <' , $to);
				if($br_id != 0) {
					$this->db->where('brand.br_id' , $br_id);
				}
				if($trans_payment_status != 'any') {
					$this->db->where('trans_payment_status' , $trans_payment_status);
				}
				$this->db->stop_cache();
				$this->db->select('SUM(trans_payout) as total_payout');
				$this->db->group_by('trans_status');
				$res = $this->db->get('transaction');
				$temp = $res->row_array(); 
				if($temp) {
					$this->sci->assign('total_payout' , $temp['total_payout']);
				}
				
				$total = $this->db->count_all_results('transaction');
				$this->sci->assign('total' , $total);
				$res = $this->db->get('transaction');
				$this->db->flush_cache();
				$result = $res->result_array();
				
				foreach($result as $k=>$tmp) {
					$this->db->where('trans_id' , $tmp['trans_id']);
					$this->db->where('transd_type' , 'Product');
					$res = $this->db->get('transaction_detail');
					$detail = $res->row_array();
					$result[$k]['detail'] = $detail;
				}
				
				//foreach($result as $k=>$tmp) {
				//	if($tmp['br_id'] != 0) {
				//		$this->db->where('br_id' , $tmp['br_id']);
				//		$res = $this->db->get('brand');
				//		$brand = $res->row_array();
				//		$result[$k]['brand'] = $brand;
				//	} else {
				//		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
				//		$this->db->where('pr_id' , $tmp['pr_id']);
				//		$res = $this->db->get('promo');
				//		$result[$k]['brand'] = $promo;
				//	}
				//}

				$this->sci->assign('result' , $result);
				//print_r($result);
			}

		}

		$this->sci->da('monthly.htm');
	}

	function monthly_change_filter() {
		$page = $this->input->post('page');
		$from_year = $this->input->post('from_year');
		$from_month = $this->input->post('from_month');
		$to_year = $this->input->post('to_year');
		$to_month = $this->input->post('to_month');
		$br_id = $this->input->post('br_id');
		$trans_payment_status = $this->input->post('trans_payment_status');
		redirect("$page$from_year/$from_month/$to_year/$to_month/$br_id/$trans_payment_status");
	}
	
	
	
	
	function daily($from=0, $to=0,$br_id=0, $trans_payment_status='Delivered') {
		$this->sci->assign('section' , 'daily');
		$this->load_topbar();
		
		$this->sci->assign('from' , $from);
		$this->sci->assign('to' , $to); 
		$this->sci->assign('br_id' , $br_id);
		$this->sci->assign('trans_payment_status' , $trans_payment_status);

		$month = date_make_selection_month();
		$this->sci->assign('month' , $month);
		$year = date_make_selection_year();
		$this->sci->assign('year' , $year);
		
		//get brands
		$this->db->where('br_status' , 'Active');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		if($to != 0 AND $from!=0 ) { 

			if($from <= $to) {  
				 
				$this->db->start_cache();
				$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left'); 
				$this->db->join('promo' , 'promo.pr_id = transaction.pr_id' , 'left');
				$this->db->join('brand' , 'brand.br_id = transaction.br_id' , 'left');
				$this->db->where('trans_status' , 'Active');
				$this->db->where('trans_payment_status' , 'Delivered');
				$this->db->where('trans_entry >=' , $from);
				$this->db->where('trans_entry <' , $to);
				if($br_id != 0) {
					$this->db->where('brand.br_id' , $br_id);
				}
				if($trans_payment_status != 'any') {
					$this->db->where('trans_payment_status' , $trans_payment_status);
				}
				$this->db->stop_cache();
				$this->db->select('SUM(trans_payout) as total_payout');
				$this->db->group_by('trans_status');
				$res = $this->db->get('transaction');
				$temp = $res->row_array();
				if($temp) {
					$this->sci->assign('total_payout' , $temp['total_payout']);
				}
				
				
				$total = $this->db->count_all_results('transaction');
				$this->sci->assign('total' , $total);
				$res = $this->db->get('transaction');
				$this->db->flush_cache();
				$result = $res->result_array();
				
				foreach($result as $k=>$tmp) {
					$this->db->where('trans_id' , $tmp['trans_id']);
					$this->db->where('transd_type' , 'Product');
					$res = $this->db->get('transaction_detail');
					$detail = $res->row_array();
					$result[$k]['detail'] = $detail;
				} 

				$this->sci->assign('result' , $result); 
			}

		}

		$this->sci->da('daily.htm');
	}

	function daily_change_filter() {
		$page = $this->input->post('page');
		$from = $this->input->post('from');
		$to= $this->input->post('to'); 
		$br_id = $this->input->post('br_id');
		$trans_payment_status = $this->input->post('trans_payment_status');
		redirect("$page$from/$to/$br_id/$trans_payment_status");
	}
	
	
	
	
	
	/////
	
	function product_monthly($from_year=0, $from_month=0, $to_year=0, $to_month=0, $br_id=0, $trans_payment_status='Delivered') {
		$this->sci->assign('section' , 'monthly');
		$this->load_topbar();
		
		$this->sci->assign('from_year' , $from_year);
		$this->sci->assign('from_month' , $from_month);
		$this->sci->assign('to_year' , $to_year);
		$this->sci->assign('to_month' , $to_month);
		$this->sci->assign('br_id' , $br_id);
		$this->sci->assign('trans_payment_status' , $trans_payment_status);

		$month = date_make_selection_month();
		$this->sci->assign('month' , $month);
		$year = date_make_selection_year();
		$this->sci->assign('year' , $year);
		
		//get brands
		$this->db->where('br_status' , 'Active');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		if($from_month != 0 AND $from_year!=0 AND $to_month!=0 AND $to_year!=0) {
			$from = "$from_year-$from_month-01";
			$to = "$to_year-$to_month-01";

			if($from <= $to) {  
				
				$from_temp = $from_year."-".$from_month."-01";
				$from = date ("Y-m-d H:i:s", strtotime($from_temp));
				$to_temp = $to_year."-".$to_month."-01";
				$to = date ("Y-m-d H:i:s", strtotime("+1 month", strtotime($to_temp)));
				
				$this->db->start_cache();
				$this->db->join('transaction' , 'transaction.trans_id = transaction_detail.trans_id' , 'left');
				$this->db->join('product' , 'product.p_id = transaction_detail.p_id' , 'left');
				$this->db->join('product_quantity' , 'product_quantity.pq_id = transaction_detail.pq_id' , 'left');
				$this->db->join('brand' , 'brand.br_id = product.br_id' , 'left');
				//$this->db->join('member' , 'member.m_id = transaction.m_id' , 'left'); 
				//$this->db->join('promo' , 'promo.pr_id = transaction.pr_id' , 'left');
				
				$this->db->where('trans_status' , 'Active');
				$this->db->where('trans_payment_status' , 'Delivered');
				$this->db->where('trans_entry >=' , $from);
				$this->db->where('trans_entry <' , $to);
				if($br_id != 0) {
					$this->db->where('brand.br_id' , $br_id);
				}
				if($trans_payment_status != 'any') {
					$this->db->where('trans_payment_status' , $trans_payment_status);
				}
				$this->db->stop_cache();
				$this->db->select('SUM(trans_payout) as total_payout');
				$this->db->group_by('trans_status');
				$res = $this->db->get('transaction_detail');
				$temp = $res->row_array(); 
				if($temp) {
					$this->sci->assign('total_payout' , $temp['total_payout']);
				}
				
				$total = $this->db->count_all_results('transaction_detail');
				$this->sci->assign('total' , $total);
				$res = $this->db->get('transaction_detail');
				$this->db->flush_cache();
				$result = $res->result_array();
				
				foreach($result as $k=>$tmp) {
					$this->db->where('trans_id' , $tmp['trans_id']);
					$this->db->where('transd_type' , 'Product');
					$res = $this->db->get('transaction_detail');
					$detail = $res->row_array();
					$result[$k]['detail'] = $detail;
				} 

				$this->sci->assign('result' , $result); 
			}

		}

		$this->sci->da('product_monthly.htm');
	}

	function product_monthly_change_filter() {
		$page = $this->input->post('page');
		$from_year = $this->input->post('from_year');
		$from_month = $this->input->post('from_month');
		$to_year = $this->input->post('to_year');
		$to_month = $this->input->post('to_month');
		$br_id = $this->input->post('br_id');
		$trans_payment_status = $this->input->post('trans_payment_status');
		redirect("$page$from_year/$from_month/$to_year/$to_month/$br_id/$trans_payment_status");
	}
	
	
	
	
	
	
	function load_trans_detail($trans_id=0) {
		$this->db->join('brand br' , 'br.br_id = transaction.br_id' , 'left');
		$this->db->where('trans_id' , $trans_id);
		$res = $this->db->get('transaction');
		$trans = $res->row_array();
		$this->sci->assign('trans' , $trans);
		
		$this->db->where('trans_id' , $trans_id);
		$this->db->join('product_quantity pq' , 'pq.pq_id = transaction_detail.pq_id' , 'left');
		$this->db->join('product p' , 'p.p_id = transaction_detail.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$res = $this->db->get('transaction_detail');
		$trans_detail = $res->result_array();
		$this->sci->assign('trans_detail' , $trans_detail);
		$this->sci->d('trans_detail.htm');
	}
	
	
	
	
	
	
	
	
	
	
	
	//////
	
	
	function fix_transaction() {
		$this->db->where('trans_type' , 'Product');
		$res = $this->db->get('transaction');
		
		$trans = $res->result_array();
		foreach($trans as $k=>$tmp) {
			$this->db->join('product' , 'product.p_id = transaction_detail.p_id' , 'left');
			$this->db->where('trans_id' , $tmp['trans_id']);
			$this->db->where('transd_type' , 'Product');
			$res = $this->db->get('transaction_detail');
			$transd = $res->row_array();
			
			if($transd) {
				$this->db->where('trans_id' , $tmp['trans_id']);
				$this->db->set('br_id' , $transd['br_id']);
				$this->db->update('transaction');	
			}
			
			
		}
	}




}
