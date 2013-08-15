<?php

class Get_connected extends MY_Controller {

	var $mod_title = 'Get Connected';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->load->model('mod_product');

		$this->session->validate_member(FALSE);
	}

	function index(){
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Get Connected";
		$this->sci->assign('breadcrumb' , $breadcrumb);


		$this->sci->da('index.htm');
	}

	function landing($brand_view_mode="list") {
		$this->sci->assign('brand_view_mode' , $brand_view_mode);

		$show_auth_landing = $this->session->flashdata('show_auth_landing');
		$this->sci->assign('show_auth_landing' , $show_auth_landing);
		$auth_lastpage = $this->session->userdata('auth_lastpage');
		$this->sci->assign('auth_lastpage' , $auth_lastpage);


		//get current brand onsale
		$this->db->where('br_status' , 'Active');
		$this->db->where('br_start_promo <=' , date('Y-m-d H:i:s') );
		$this->db->where('br_end_promo >' , date('Y-m-d H:i:s') );
		$this->db->limit(6);
		$res = $this->db->get('brand');
		$brand_onsale = $res->result_array();
		foreach($brand_onsale as $k=>$tmp) {
			$diff = get_time_difference(date('Y-m-d H:i:s'), $tmp['br_end_promo']);
			$brand_onsale[$k]['time_diff'] = $diff;
			//print_r($diff);
		}
		$this->sci->assign('brand_onsale' , $brand_onsale);

		//get brand coming soon
		$this->db->where('br_status' , 'Active');
		$this->db->where('br_start_promo >' , date('Y-m-d H:i:s') );
		$this->db->limit(6);
		$res = $this->db->get('brand');
		$brand_comingsoon = $res->result_array();
		foreach($brand_comingsoon as $k=>$tmp) {
			$diff = get_time_difference( date('Y-m-d H:i:s'), $tmp['br_start_promo']);
			$brand_comingsoon[$k]['time_diff'] = $diff;
			//print_r($diff);
		}
		$this->sci->assign('brand_comingsoon' , $brand_comingsoon);

		//get main banner
		$this->db->where('bn_status' , 'Active');
		$this->db->order_by('bn_order' , 'ASC');
		$res = $this->db->get('banner');
		$mainbanner = $res->result_array();
		$this->sci->assign('mainbanner' , $mainbanner);

		//get featured product
		$join[] = array('brand', 'brand.br_id = product.br_id', 'left');
		$join[] = array('product_category', 'product_category.pc_id = product.pc_id', 'left');
		$featured_product = $this->mod_product->get('*', array('p_is_featured'=>'Yes'), $join );
		//print_r($featured_product);
		$this->sci->assign('featured_product' , $featured_product);

		$this->sci->da('index.htm');
	}

}
