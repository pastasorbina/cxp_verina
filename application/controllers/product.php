<?php

class Product extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		//$this->sci->set_view_folder('product/');
		$this->_init();
		$this->load->model('mod_product');
		$this->load->model('mod_brand');
		$this->session->validate_member(FALSE);
	}

	function index($pc_slug='', $sub1='', $sub2='', $sub3='', $sub4=''){
		$this->sci->da('index.htm');
	}

	function view($p_id=0, $p_slug=''){
		$product = $this->mod_product->get_product_by_id($p_id);
		$this->sci->assign('product' , $product);

		$also_bought = $this->mod_product->get_new_arrivals(5);
		$this->sci->assign('also_bought' , $also_bought);

		$this->sci->da('view.htm');
	}

	function test_view(){
		$this->sci->da('product/test_view.htm', TRUE);
	}

	function new_arrivals() {
		$maindata = $this->mod_product->get_new_arrivals();
		$this->sci->assign('maindata' , $maindata);

		$this->sci->load_sidebar();
		$this->sci->da('new_arrivals.htm');
	}



	//function ajax_get_quantity($pq_id=''){
	//	$this->db->where('pq_id' , $pq_id);
	//	$res = $this->db->get('product_quantity');
	//	$result = $res->row_array();
	//	$ret['quantity'] = number_format($result['pq_quantity'],0);
	//	print json_encode($ret);
	//}
	//
	//function view( $pr_id=0, $p_id=0) {
	//	$this->session->set_lastpage();
	//	$callback_url = current_url();
	//	$callback_url = safe_base64_encode($callback_url);
	//	$this->session->set_userdata('callback_url', $callback_url);
	//	$this->sci->assign('callback_url' , $callback_url);
	//
	//	//get promo
	//	$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
	//	$this->db->where('pr_id' , $pr_id);
	//	$res = $this->db->get('promo');
	//	$promo = $res->row_array();
	//
	//	//get product
	//	$this->db->join('brand br' , 'br.br_id = product.br_id' , 'left');
	//	$this->db->join('product_category pc' , 'pc.pc_id = product.pc_id' , 'left');
	//	$this->db->where('p_id' , $p_id);
	//	$res = $this->db->get('product');
	//	$product = $res->row_array();
	//
	//	if(!$product) { show_404(); }
	//
	//	//check if this product's brand is not onsale, show 404
	//	$brand_onsale_list = $this->mod_brand->get_brand_onsale_list();
	//	if(!in_array($product['br_id'], $brand_onsale_list)) {  show_404(); }
	//
	//	$this->sci->assign('promo' , $promo);
	//	$this->sci->assign('product' , $product);
	//	$this->sci->assign('pr_id' , $pr_id);
	//	$this->sci->assign('p_id' , $p_id);
	//
	//	//get content by categoryid
	//	$this->db->where('pc_id' , $product['pc_id']);
	//	$this->db->where('pt_id' , $product['pt_id']);
	//	$this->db->where('c_status' , 'Active');
	//	$res = $this->db->get('content');
	//	$size_chart = $res->row_array();
	//	$this->sci->assign('size_chart' , $size_chart);
	//	if($size_chart) {
	//		$this->db->where('c_id' , $size_chart['c_parent_id']);
	//		$this->db->where('c_status' , 'Active');
	//		$res = $this->db->get('content');
	//		$size_chart_parent = $res->row_array();
	//		$this->sci->assign('size_chart_parent' , $size_chart_parent);
	//	}
	//
	//
	//	//get delivery guide
	//	//get brand delivery guide
	//	if(trim($promo['pr_delivery_guide']) != '') {
	//		$delivery_guide = $promo['pr_delivery_guide'];
	//		$delivery_guide_type = 'single';
	//	} else {
	//
	//		if(trim($product['br_delivery_guide']) != '') {
	//			$delivery_guide = $product['br_delivery_guide'];
	//			$delivery_guide_type = 'single';
	//		} else {
	//			$this->db->where('c_code' , 'delivery-guide');
	//			$this->db->where('c_status' , 'Active');
	//			$res = $this->db->get('content');
	//			$delivery_guide = $res->row_array();
	//			$this->db->where('c_parent_id' , $delivery_guide['c_id']);
	//
	//			$this->db->where('c_status' , 'Active');
	//			$res = $this->db->get('content');
	//			$delivery_guide['children'] = $res->result_array();
	//
	//			$delivery_guide_type = 'tree';
	//		}
	//
	//	}
	//	$this->sci->assign('delivery_guide' , $delivery_guide);
	//	$this->sci->assign('delivery_guide_type' , $delivery_guide_type);
	//
	//
	//
	//	//get product quantity
	//	$this->db->where('p_id' , $p_id);
	//	$this->db->where('pq_status' , 'Active');
	//	$res = $this->db->get('product_quantity');
	//	$product_quantity = $res->result_array();
	//	$this->sci->assign('product_quantity' , $product_quantity);
	//
	//	//product quantity list
	//	$pq_list = array();
	//	foreach($product_quantity as $k=>$tmp) {
	//		$pq_list['pq_quantity'][$k] = $tmp['pq_quantity'];
	//		$pq_list['pq_size'][$k] = $tmp['pq_size'];
	//	}
	//	$this->sci->assign('pq_list' , $pq_list);
	//
	//	//load other like it, get rest of product in promo
	//	$related_product = $this->mod_brand->get_related_product($pr_id, $p_id, 3);
	//	foreach($related_product as $k=>$tmp) {
	//		//get product quantity
	//		$this->db->where('p_id' , $tmp['p_id']);
	//		$this->db->where('pq_quantity !=' , 0);
	//		$this->db->where('pq_status' , 'Active');
	//		$res = $this->db->get('product_quantity');
	//		$product_quantity = $res->result_array();
	//		$related_product[$k]['quantity'] = $product_quantity;
	//	}
	//	$this->sci->assign('related_product' , $related_product);
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = '<a href="'.site_url().'brand/view/'.$pr_id.' ">'. $promo['br_name'].'</a>';
	//	$breadcrumb[] = $product['p_name'];
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->da('view.htm');
	//}
	//
	//
	//
	//
	//
	//
	//
	//
	//function index() {
	//	$this->session->set_bread('list');
	//
	//	//get product images (from content placeholder)
	//	$this->db->where('c_code' , 'product');
	//	$this->db->join('content' , 'content.c_id = content_image.c_id' , 'left');
	//	$this->db->order_by('ci_order' , 'asc');
	//	$res = $this->db->get('content_image');
	//	$content_image = $res->result_array();
	//	$this->sci->assign('content_image' , $content_image);
	//
	//	$this->db->where('pc_parent_id', 0);
	//	$this->db->where('product_category.b_id' , $this->branch_id);
	//	$this->db->where('pc_status', 'Active');
	//	$res = $this->db->get('product_category');
	//	$maindata = $res->result_array();
	//
	//	foreach($maindata as $k=>$tmp){
	//		$this->db->where('pc_parent_id', $tmp['pc_id']);
	//		$this->db->where('product_category.b_id' , $this->branch_id);
	//		$this->db->where('pc_status', 'Active');
	//		$res = $this->db->get('product_category');
	//		$child = $res->result_array();
	//		$maindata[$k]['child'] = $child;
	//		foreach($child as $k2=>$tmp2){
	//			$this->db->where('pc_id' , $tmp2['pc_id']);
	//			$this->db->where('p_status' , 'Active');
	//			$this->db->where('product.b_id' , $this->branch_id);
	//			$this->db->order_by('p_date' , 'ASC');
	//			$res = $this->db->get('product');
	//			$product = $res->result_array();
	//			$maindata[$k]['child'][$k2]['item'] = $product;
	//		}
	//	}
	//	$this->sci->assign('maindata' , $maindata);
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
	//	$breadcrumb[] = "Food & Drinks";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->da('index.htm');
	//}
	//
	//function subcategory($pc_id=0, $pc_slug='', $offset=0) {
	//	$this->db->where('pc_id' , $pc_id);
	//	$this->db->where('product_category.b_id' , $this->branch_id);
	//	$this->db->where('pc_status', 'Active');
	//	$res = $this->db->get('product_category');
	//	$product_category = $res->row_array();
	//
	//	if(!$product_category) { show_404(); }
	//
	//	$product_category['slug'] = make_slug($product_category['pc_name']);
	//	$this->sci->assign('product_category' , $product_category);
	//
	//	$this->db->where('pc_id' , $product_category['pc_parent_id'] );
	//	$this->db->where('product_category.b_id' , $this->branch_id);
	//	$this->db->where('pc_status', 'Active');
	//	$res = $this->db->get('product_category');
	//	$parent = $res->row_array();
	//	$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
	//	$this->db->where('mr_foreign_id' , $parent['pc_id']);
	//	$this->db->where('mr_module' , 'product_category');
	//	$res = $this->db->get('media_relation');
	//	$media = $res->result_array();
	//	foreach($media as $l=>$tmp) {
	//		$pos = $tmp['mr_pos'];
	//		$parent['media'][$pos] = $tmp;
	//	}
	//	$this->sci->assign('parent' , $parent);
	//
	//
	//	$pagelimit = 10;
	//	$this->db->start_cache();
	//	$this->db->where('pc_id' , $product_category['pc_id']);
	//	$this->db->where('p_status' , 'Active');
	//	$this->db->where('product.b_id' , $this->branch_id);
	//	$this->db->order_by('p_date' , 'DESC');
	//	$this->db->stop_cache();
	//	// Pagination
	//	$total = $this->db->count_all_results('product');
	//	$this->load->library('pagination');
	//	$config['base_url'] = $this->mod_url."subcategory/$pc_id/$pc_slug";
	//	$config['suffix'] = "/" ;
	//	$config['total_rows'] = $total;
	//	$config['per_page'] = $pagelimit;
	//	$config['uri_segment'] = 5;
	//	$this->pagination->initialize($config);
	//	$this->db->limit($pagelimit, $offset);
	//	$res = $this->db->get('product');
	//	$this->db->flush_cache();
	//	$product = $res->result_array();
	//	$this->sci->assign('product' , $product);
	//	$this->sci->assign('paging', $this->pagination->create_links() );
	//
	//	$this->sci->assign('module_title' , $product_category['pc_name']);
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "Product";
	//	$breadcrumb[] = $parent['pc_name'];
	//	$breadcrumb[] = "<a class='active' href='".site_url()."product/subcategory/".$product_category['pc_id']."/".$product_category['slug']."' >".$product_category['pc_name']."</a>";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->da('subcategory.htm');
	//}
	//
	//
	//
	//function list_gift_card() {
	//	$this->sci->assign('currpage' , 'giftcard');
	//	$html = $this->sci->fetch('default/sidebar_voucher_giftcard.htm');
	//	$this->sci->assign('sidebar' , $html);
	//
	//	$this->db->where('p_type' , 'Giftcard');
	//	$this->db->where('p_is_published' , 'Yes');
	//	$this->db->where('p_status' , 'Active');
	//	$this->db->order_by('p_order' , 'ASC');
	//	$res = $this->db->get('product');
	//	$products = $res->result_array();
	//	$this->sci->assign('products' , $products);
	//
	//	$breadcrumb = array();
	//	$breadcrumb[] = "<a class='active' href='".site_url()."/'>Home</a>";
	//	$breadcrumb[] = "Product";
	//	$breadcrumb[] = "Gift Card";
	//	$this->sci->assign('breadcrumb' , $breadcrumb);
	//
	//	$this->sci->da('list_gift_card.htm');
	//}






}
