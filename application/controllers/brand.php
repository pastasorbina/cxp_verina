<?php

class Brand extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->load->model('mod_brand');


		//get all_category
		$this->db->where('pc_status' , 'Active');
		$res = $this->db->get('product_category');
		$all_product_category = $res->result_array();
		$this->sci->assign('all_product_category' , $all_product_category);
		
		//get all type, regardless what selection user made
		$this->db->where('pt_status' , 'Active');
		$res = $this->db->get('product_type');
		$all_product_type = $res->result_array();
		$this->sci->assign('all_product_type' , $all_product_type);
	}

	function index() {
		$this->view();
	}

	function change_filter() {
		$page = $this->input->post('page');
		$pc_id = $this->input->post('pc_id');
		$psc_id = $this->input->post('psc_id');
		$pt_id = $this->input->post('pt_id');
		$pq_size = $this->input->post('pq_size');

		if(!$psc_id) $psc_id = 0;

		$url = "$page$pt_id/$pc_id/$psc_id/$pq_size";
		redirect($url);
	}
	
	function view($pr_id=0, $pt_id=0, $pc_id=0, $psc_id=0, $encoded_pq_size='any', $pagelimit=9, $offset=0) {
		$this->session->validate_member(FALSE);
		$this->session->set_bread('brand-view');
		//assign params
		$this->sci->assign('pc_id' , $pc_id);
		$this->sci->assign('psc_id' , $psc_id);
		$this->sci->assign('pt_id' , $pt_id);
		$this->sci->assign('encoded_pq_size' , $encoded_pq_size);
		$pq_size = str_replace('_', ' ', $encoded_pq_size);
		$this->sci->assign('pq_size' , $pq_size); 
		//assign default filter params
		$this->sci->assign('pr_id' , $pr_id);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);

		//get promo AND promo_products
		$promo = $this->mod_brand->get_promo_by_id($pr_id);
		$br_id = 0;
		$promo_products = array();
		$promo_p_id_list = array();
		$promo_products_array = array();
		if($promo) {
			$br_id = $promo['pr_br_id'];
			$promo_products = $this->mod_brand->get_promo_products($pr_id); 
			foreach($promo_products as $k=>$tmp) {
				$promo_p_id_list[$k] = $tmp['p_id']; 
				$promo_products_array['p_id'][$k] = $tmp['p_id'];
				$promo_products_array['pc_id'][$k] = $tmp['pc_id'];
				$promo_products_array['psc_id'][$k] = $tmp['psc_id'];
				$promo_products_array['br_id'][$k] = $tmp['br_id'];
				$promo_products_array['pt_id'][$k] = $tmp['pt_id'];
			}
			
			//get time diff
			$diff = get_time_difference(date('Y-m-d H:i:s'), $promo['pr_end_promo']);
			$promo['time_diff'] = $diff;
		}
		//print_r($promo_products_array);
		if(!$promo_products_array) { redirect(site_url()); }
		
		//print_r($promo_products_array);
		$this->sci->assign('promo' , $promo); 
		$this->sci->assign('promo_products' , $promo_products); 
		$this->sci->assign('promo_p_id_list' , $promo_p_id_list); 
		//get product types of products listed
		$this->db->where_in('pt_id' , $promo_products_array['pt_id'] );
		$res = $this->db->get('product_type');
		$all_product_type = $res->result_array(); 
		$this->sci->assign('all_product_type' , $all_product_type); 
		//get product categories of products listed
		$this->db->where_in('pc_id' , $promo_products_array['pc_id'] );
		$res = $this->db->get('product_category');
		$all_product_category = $res->result_array(); 
		$this->sci->assign('all_product_category' , $all_product_category);
		
		
		//get product subcategories of products listed
		if($pc_id !=0) {  
			$subcategory = $this->mod_product->get_product_subcategories_by_pc_id($pc_id);
			$this->sci->assign('subcategory' , $subcategory);
		} 
		//get all product quantities
		if($pt_id !=0 && $pc_id !=0  ) {
			$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
			$this->db->join('product_type pt' , 'pt.pt_id = product.pt_id' , 'left');
			$this->db->where('product.pt_id' , $pt_id);
			$this->db->where('product.pc_id' , $pc_id);
			$this->db->where('p_status' , 'Active');
			$this->db->order_by('p_order' , 'ASC');
			$this->db->select('p_id');
			$res = $this->db->get('product');
			$proda = $res->result_array();
			$prod = array();
			foreach($proda as $k=>$tmp) { $prod[] = $tmp['p_id']; }

			if($prod){ $this->db->where_in('p_id', $prod); }
			$this->db->where('pq_status' , 'Active');
			$this->db->order_by('pq_size' , 'DESC');
			$this->db->group_by('pq_size');
			$this->db->select('pq_size, p_id, pq_id, pq_status');
			$res = $this->db->get('product_quantity');
			$all_size = $res->result_array();
			$this->sci->assign('all_size' , $all_size);
		}  

		$pquantity_pid = array();
		if($pq_size !='any' ) {
			$this->db->where('pq_size' , $pq_size);
			$this->db->where('pq_status' , 'Active');
			$this->db->select('p_id');
			$res = $this->db->get('product_quantity');
			$pquantity = $res->result_array();
			foreach($pquantity as $k=>$tmp) {
				$pquantity_pid[$k] = $tmp['p_id'];
			}
		} 
		
		//starting to get all products
		$this->db->start_cache();
		$this->db->join('brand' , 'brand.br_id = product.br_id' , 'left');
		$this->db->join('product_subcategory psc' , 'psc.psc_id = product.psc_id' , 'left');
		$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
		$this->db->join('product_type pt' , 'pt.pt_id = product.pt_id' , 'left');
		$this->db->where('p_status' , 'Active');
		$this->db->order_by('p_order' , 'ASC'); 
		//filter only products that is included in the promo
		if(sizeof($promo_p_id_list) > 0) { $this->db->where_in('p_id' , $promo_p_id_list); } 
		//filter by category, subcategory and type
		if($pc_id !=0 ) { $this->db->where('pc.pc_id' , $pc_id); }
		if($psc_id !=0 ) { $this->db->where('psc.psc_id' , $psc_id); }
		if($pt_id !=0 ) { $this->db->where('pt.pt_id' , $pt_id); }
		//filter by product quantity
		if($pq_size !='any' AND $pquantity_pid ) { $this->db->where_in('p_id' , $pquantity_pid); }

		$this->db->stop_cache(); 
		$total = $this->db->count_all_results('product');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."view/$pr_id/$pt_id/$pc_id/$pc_id/$encoded_pq_size/$pagelimit";
		$config['suffix'] = "/" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] =9;
		$this->pagination->initialize($config); 
		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('product');
		$this->db->flush_cache();
		$maindata = $res->result_array();
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

		//assigning  breadcrumb
		//$pt_id=0, $pc_id=0, $pq_size='any'
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $promo['pr_name'];
		//breadcrumb for product type
		if($pt_id == 0) {
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/0' >All Type</a>";
		} else {
			$this->db->where('pt_id' , $pt_id);
			$res = $this->db->get('product_type');
			$pt_breadcrumb = $res->row_array();
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/".$pt_id."' >".$pt_breadcrumb['pt_name']."</a>";
		}
		//breadcrumb for product category
		if($pc_id == 0) {
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/".$pt_id."/0' >All Category</a>";
		} else {
			$this->db->where('pc_id' , $pc_id);
			$res = $this->db->get('product_category');
			$pc_breadcrumb = $res->row_array();
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/".$pt_id."/".$pc_id."' >".$pc_breadcrumb['pc_name']."</a>";

		}

		if($psc_id == 0) {
		} else {
			$this->db->where('psc_id' , $psc_id);
			$res = $this->db->get('product_subcategory');
			$psc_breadcrumb = $res->row_array();
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/".$pt_id."/".$pc_id."/".$psc_id."' >".$psc_breadcrumb['psc_name']."</a>";
		}

		//breadcrumb for product size
		if($pq_size == 'any') { 
		} else {
			$breadcrumb[] = "<a href='".site_url()."brand/view/".$pr_id."/".$pt_id."/".$pc_id."/".$encoded_pq_size."' >Size: ".$pq_size."</a>";
		}
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('view.htm');
	}


	//function view123_old($brp_id=0, $pt_id=0, $pc_id=0, $psc_id=0, $encoded_pq_size='any', $pagelimit=9, $offset=0) {
	//
	//	$this->session->validate_member(FALSE);
	//	$this->session->set_bread('brand-view');
	//
	//	//$this->_load_sidebar();
	//	$this->sci->assign('pc_id' , $pc_id);
	//	$this->sci->assign('psc_id' , $psc_id);
	//	$this->sci->assign('pt_id' , $pt_id);
	//	$this->sci->assign('encoded_pq_size' , $encoded_pq_size);
	//	$pq_size = str_replace('_', ' ', $encoded_pq_size);
	//	$this->sci->assign('pq_size' , $pq_size);
	//
	//
	//	//assign default filter params
	//	$this->sci->assign('brp_id' , $brp_id);
	//	$this->sci->assign('pagelimit' , $pagelimit);
	//	$this->sci->assign('offset' , $offset);
	//
	//	//get promo
	//	$promo = $this->mod_brand->get_promo_by_id($brp_id);
	//	$br_id = 0;
	//	if($promo) {
	//		$br_id = $promo['br_id'];
	//	}
	//	$this->sci->assign('promo' , $promo);
	//
	//	if($pt_id !=0 ) {
	//		$this->db->where('pt_id' , $pt_id);
	//		$this->db->where('p_status' , 'Active');
	//		$this->db->order_by('p_order' , 'ASC');
	//		$this->db->group_by('pc_id');
	//		$this->db->select('pc_id');
	//		$res = $this->db->get('product');
	//		$prod = $res->result_array();
	//
	//		$all_product_category = array();
	//		foreach($prod as $k=>$tmp) {
	//			$this->db->where('pc_id', $tmp['pc_id']);
	//			$this->db->where('pc_status' , 'Active');
	//			$res = $this->db->get('product_category');
	//			$all_product_category[$k] = $res->row_array();
	//		}
	//		$this->sci->assign('all_product_category' , $all_product_category);
	//	}
	//
	//	if($pt_id !=0 && $pc_id !=0  ) {
	//		$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
	//		$this->db->join('product_type pt' , 'pt.pt_id = product.pt_id' , 'left');
	//		$this->db->where('product.pt_id' , $pt_id);
	//		$this->db->where('product.pc_id' , $pc_id);
	//		$this->db->where('p_status' , 'Active');
	//		$this->db->order_by('p_order' , 'ASC');
	//		$this->db->select('p_id');
	//		$res = $this->db->get('product');
	//		$proda = $res->result_array();
	//		$prod = array();
	//		foreach($proda as $k=>$tmp) { $prod[] = $tmp['p_id']; }
	//
	//		if($prod){ $this->db->where_in('p_id', $prod); }
	//		$this->db->where('pq_status' , 'Active');
	//		$this->db->order_by('pq_size' , 'DESC');
	//		$this->db->group_by('pq_size');
	//		$this->db->select('pq_size, p_id, pq_id, pq_status');
	//		$res = $this->db->get('product_quantity');
	//		$all_size = $res->result_array();
	//		$this->sci->assign('all_size' , $all_size);
	//	}
	//
	//	if($pc_id !=0) {
	//		$this->db->where('pc_id' , $pc_id);
	//		$this->db->where('psc_status' , 'Active');
	//		$this->db->order_by('psc_name' , 'asc');
	//		$res = $this->db->get('product_subcategory');
	//		$subcategory = $res->result_array();
	//		$this->sci->assign('subcategory' , $subcategory);
	//	}
	//
	//	$pquantity_pid = array();
	//	if($pq_size !='any' ) {
	//		$this->db->where('pq_size' , $pq_size);
	//		$this->db->where('pq_status' , 'Active');
	//		$this->db->select('p_id');
	//		$res = $this->db->get('product_quantity');
	//		$pquantity = $res->result_array();
	//		foreach($pquantity as $k=>$tmp) {
	//			$pquantity_pid[$k] = $tmp['p_id'];
	//		}
	//	}
	//
	//	//$this->db->where('br_id' , $br_id);
	//	//$res = $this->db->get('brand');
	//	//$this_brand  = $res->row_array();
	//	//$this->sci->assign('this_brand' , $this_brand);
	//
	//	$this->db->start_cache();
	//	$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
	//	$this->db->join('product_type pt' , 'pt.pt_id = product.pt_id' , 'left');
	//	$this->db->where('p_status' , 'Active');
	//	$this->db->order_by('p_order' , 'ASC');
	//	$this->db->where('br_id' , $br_id);
	//	$this->db->order_by('p_order' , 'asc');
	//	//start filter
	//	if($pc_id !=0 ) {
	//		$this->db->where('pc.pc_id' , $pc_id);
	//	}
	//	if($psc_id !=0 ) {
	//		$this->db->where('psc_id' , $psc_id);
	//	}
	//	if($pt_id !=0 ) { $this->db->where('pt.pt_id' , $pt_id); }
	//	if($pq_size !='any' AND $pquantity_pid ) { $this->db->where_in('p_id' , $pquantity_pid); }
	//
	//
	//	$this->db->stop_cache();
	//
	//	$total = $this->db->count_all_results('product');
	//	$this->load->library('pagination');
	//	$config['base_url'] = $this->mod_url."view/$brp_id/$pt_id/$pc_id/$pc_id/$encoded_pq_size/$pagelimit";
	//	$config['suffix'] = "/" ;
	//	$config['total_rows'] = $total;
	//	$config['per_page'] = $pagelimit;
	//	$config['uri_segment'] =9;
	//	$this->pagination->initialize($config);
	//
	//	$this->db->limit($pagelimit, $offset);
	//	$res = $this->db->get('product');
	//	$this->db->flush_cache();
	//	$maindata = $res->result_array();
	//	foreach($maindata as $k=>$tmp) {
	//		//get product quantity
	//		$this->db->where('p_id' , $tmp['p_id']);
	//		$this->db->where('pq_quantity !=' , 0);
	//		$this->db->where('pq_status' , 'Active');
	//		$res = $this->db->get('product_quantity');
	//		$product_quantity = $res->result_array();
	//		$maindata[$k]['quantity'] = $product_quantity;
	//
	//	}
	//
	//	$this->sci->assign('maindata' , $maindata);
	//	$this->sci->assign('paging', $this->pagination->create_links() );
	//
	//	//assign breadcrumb
	//	//$pt_id=0, $pc_id=0, $pq_size='any'
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = $promo['br_name'];
	//	//breadcrumb for product type
	//	if($pt_id == 0) {
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/0' >All Type</a>";
	//	} else {
	//		$this->db->where('pt_id' , $pt_id);
	//		$res = $this->db->get('product_type');
	//		$pt_breadcrumb = $res->row_array();
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/".$pt_id."' >".$pt_breadcrumb['pt_name']."</a>";
	//	}
	//	//breadcrumb for product category
	//	if($pc_id == 0) {
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/".$pt_id."/0' >All Category</a>";
	//	} else {
	//		$this->db->where('pc_id' , $pc_id);
	//		$res = $this->db->get('product_category');
	//		$pc_breadcrumb = $res->row_array();
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/".$pt_id."/".$pc_id."' >".$pc_breadcrumb['pc_name']."</a>";
	//
	//	}
	//
	//	if($psc_id == 0) {
	//	} else {
	//		$this->db->where('psc_id' , $psc_id);
	//		$res = $this->db->get('product_subcategory');
	//		$psc_breadcrumb = $res->row_array();
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/".$pt_id."/".$pc_id."/".$psc_id."' >".$psc_breadcrumb['psc_name']."</a>";
	//	}
	//
	//	//breadcrumb for product size
	//	if($pq_size == 'any') {
	//		//$breadcrumb[] = "<a href='".site_url()."brand/view/".$br_id."/".$pt_id."/".$pc_id."' >any</a>";
	//	} else {
	//		$breadcrumb[] = "<a href='".site_url()."brand/view/".$brp_id."/".$pt_id."/".$pc_id."/".$encoded_pq_size."' >Size: ".$pq_size."</a>";
	//	}
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->da('view.htm');
	//}


	function view_for_menu( $pt_id=0 ) {
		$this->sci->assign('pt_id' , $pt_id);

		//get for menu on sale 
		$promo_onsale = $this->mod_brand->get_promo_onsale(); 
		$menu_onsale = array();
		$a=0;
		foreach($promo_onsale as $k=>$tmp) {
			//get promo product
			$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
			$this->db->where('pt_id' , $pt_id);
			$this->db->where('pr_id' , $tmp['pr_id']); 
			$count_product = $this->db->count_all_results('promo_detail'); 
			if($count_product > 0) {
				$menu_onsale[$a] = $tmp;
				$diff = get_time_difference(date('Y-m-d H:i:s'), $tmp['pr_end_promo']);
				$menu_onsale[$a]['time_diff'] = $diff;
				$a++;
			}
		}
		$this->sci->assign('menu_onsale' , $menu_onsale); 
		 

		//get for menu coming soon 
		$promo_soon = $this->mod_brand->get_promo_comingsoon(); 
		$menu_soon = array();
		$a=0;
		foreach($promo_soon as $k=>$tmp) {
			$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
			$this->db->where('pt_id' , $pt_id);
			$this->db->where('pr_id' , $tmp['pr_id']); 
			$count_product = $this->db->count_all_results('promo_detail');
			if($count_product > 0) {
				$menu_soon[$a] = $tmp;
				$diff = get_time_difference(date('Y-m-d H:i:s'), $tmp['pr_start_promo']);
				$menu_soon[$a]['time_diff'] = $diff;
				$a++;
			}
		}
		$this->sci->assign('menu_soon' , $menu_soon);

		$this->sci->d('view_for_menu.htm');
	}

	function get_time_left($br_id=0){
		$current_time = date('Y-m-d H:i:s');
		//get brand
		$this->db->where('br_id' , $br_id);
		$res = $this->db->get('brand');
		$brand = $res->row_array();
		if(!$brand){ return FALSE; }
		$end_time = $brand['br_end_promo'];

		$difference = datediff($current_time, $end_time, 'array');
		echo json_encode($difference);
	}



}
