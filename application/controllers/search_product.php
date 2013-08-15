<?php

class Search_product extends MY_Controller {

	var $mod_title = '';


	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->session->validate_member(FALSE);
	}

	function do_search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		//$searchby = $this->input->post('searchby');
		//$pagelimit = $this->input->post('pagelimit');
		//$orderby = $this->input->post('orderby');
		//$offset = $this->input->post('offset');
		//$ascdesc = $this->input->post('ascdesc');
		$searchkey = safe_base64_encode(trim($searchkey));
		if( !$searchkey ) { $searchkey = ''; }
		redirect($this->mod_url."index/0/$searchkey");
	}

	function index($offset=0, $encodedkey='') {
		//if($this->branch_id != 1) { show_404();  }
		//$this->output->enable_profiler(TRUE);

		$pagelimit = 32;

		$this->search_in = array('p_name', 'br_name', 'pc_name', 'pt_name' );
		
		$promo_onsale = $this->mod_brand->get_promo_onsale_list();
		//print_r($promo_onsale);
		
		$head_query = "SELECT * FROM (`promo_detail`)";
		$mid_query = " 
			LEFT JOIN `promo` ON `promo`.`pr_id` = `promo_detail`.`pr_id`
			LEFT JOIN `brand` ON `brand`.`br_id` = `promo`.`pr_br_id`
			LEFT JOIN `product` ON `product`.`p_id` = `promo_detail`.`p_id`
			LEFT JOIN `product_category` pc ON `pc`.`pc_id` = `product`.`pc_id`
			LEFT JOIN `product_type` pt ON `pt`.`pt_id` = `product`.`pt_id`
			LEFT JOIN `product_subcategory` psc ON `psc`.`psc_id` = `product`.`psc_id`
			";
		
		$where_query = "";
		if(sizeof($promo_onsale) > 0) {
			$wherein = '(';
			foreach($promo_onsale as $k=>$tmp) {
				//print_r($tmp);
				$wherein .= "'".$tmp."'";
				if($k < (sizeof($promo_onsale)-1)){ $wherein .= ","; }
			}
			$wherein .= ')';
			$where_query .= "WHERE `promo`.`pr_id` IN $wherein";
		}
		$where_query .= "AND `p_status` =  'Active'"; 
		
		$search_query = "";
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			$search_query .= "AND (";
			$search_query .= "br_name LIKE '%".$searchkey."%'";
			$search_query .= " OR ";
			$search_query .= "p_name LIKE '%".$searchkey."%'";
			$search_query .= " OR ";
			$search_query .= "pc_name LIKE '%".$searchkey."%'";
			$search_query .= " OR ";
			$search_query .= "pt_name LIKE '%".$searchkey."%'";
			$search_query .= ")"; 
			$this->sci->assign('searchkey' , $searchkey);
		}
			
		$end_query = "  
			ORDER BY `p_name` ASC
			LIMIT 32  
		";
		
		$head_query_count = "SELECT COUNT(*) AS `numrows` FROM (`promo_detail`)";
		$query_count = $head_query_count.$mid_query.$where_query.$search_query.$end_query;
		$res = $this->db->query($query_count);
		$result_count = $res->row();
		$total = $result_count->numrows;
		
		$query = $head_query.$mid_query.$where_query.$search_query.$end_query;
		$res = $this->db->query($query);
		$maindata = $res->result_array(); 
		

		//$this->db->start_cache();
		//$this->db->join('promo' , 'promo.pr_id = promo_detail.pr_id' , 'left');
		//$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
		//$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
		//$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
		//$this->db->join('product_type pt' , 'pt.pt_id = product.pt_id' , 'left');
		//$this->db->join('product_subcategory psc' , 'psc.psc_id = product.psc_id' , 'left');
		//
		//$this->db->where_in('promo.pr_id' , $promo_onsale); 
		//$this->db->where('p_status' , 'Active');
		//$this->db->order_by('p_name' , 'ASC');
		//if($encodedkey != ''){
		//	$searchkey = safe_base64_decode($encodedkey);
		//	//foreach($this->search_in as $k=>$tmp) {
		//	//	$this->db->or_like($tmp, $searchkey);
		//	//}
		//	$this->db->like('p_name', $searchkey);
		//	$this->db->like('br_name', $searchkey);
		//	$this->db->or_like('pc_name', $searchkey);
		//	$this->db->or_like('pt_name', $searchkey);
		//	$this->sci->assign('searchkey' , $searchkey);
		//}
		//$this->db->stop_cache();
		//
		//
		//$total = $this->db->count_all_results('promo_detail');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/";
		$config['suffix'] = "/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 3;
		$this->pagination->initialize($config);

		//$this->db->limit($pagelimit, $offset);
		//$res = $this->db->get('promo_detail');
		//$this->db->flush_cache();
		$maindata = $res->result_array();
		//print_r($maindata);

		foreach($maindata as $k=>$tmp) {
			//get product quantity
			$this->db->where('p_id' , $tmp['p_id']);
			$this->db->where('pq_quantity !=' , 0);
			$this->db->where('pq_status' , 'Active');
			$res = $this->db->get('product_quantity');
			$product_quantity = $res->result_array();
			$maindata[$k]['quantity'] = $product_quantity; 
		}

		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Search";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('index.htm');
	}


}
