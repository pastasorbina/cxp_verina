<?php

class Home extends MY_Controller {

	var $mod_title = 'Home';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->sci->set_view_folder('home/');
		$this->_init();
		$this->session->validate_member(FALSE);
		$this->sci->load_sidebar();
	}

	function index($param='asd'){

		$new_arrivals = $this->mod_product->get_new_arrivals();
		$this->sci->assign('new_arrivals' , $new_arrivals);


		$this->sci->da('index.htm');
	}

	function landing($brand_view_mode="list") {
		$this->sci->assign('brand_view_mode' , $brand_view_mode);

		//print_r($this->site_config);

		//determine should i show login box ? if cookie is not set, then Yes
		$dont_show_login = "No";
		if(isset($_COOKIE["dont_show_login"])) {
			$dont_show_login = $_COOKIE["dont_show_login"];
		} else {
			setcookie("dont_show_login", "Yes", time()+1800); //expire in 0.5 hour
		}
		$this->sci->assign('dont_show_login' , $dont_show_login);

		//determine last page for next auth page
		$show_auth_landing = $this->session->flashdata('show_auth_landing');
		$this->sci->assign('show_auth_landing' , $show_auth_landing);
		$auth_lastpage = $this->session->userdata('auth_lastpage');
		$this->sci->assign('auth_lastpage' , $auth_lastpage);


		$brand_onsale = $this->mod_brand->get_promo_onsale(0);
		$this->sci->assign('brand_onsale' , $brand_onsale);


		$brand_comingsoon = $this->mod_brand->get_promo_comingsoon(0);
		$this->sci->assign('brand_comingsoon' , $brand_comingsoon);

		//get main banner
		$mainbanner = array();
		$today = date('Y-m-d H:i:s');

		$this->db->where('bn_status' , 'Active');
		$this->db->order_by('bn_order' , 'ASC');
		$res = $this->db->get('banner');
		$tmpbanner = $res->result_array();
		$i=0;
		foreach($tmpbanner as $k=>$tmp) {
			$type = $tmp['bn_type'];
			if( $type == 'Static') {
				$mainbanner[$i]['data'] = $tmp;
				$mainbanner[$i]['order'] = $tmp['bn_order'];
				$i++;
			} elseif( $type == 'Timed' ) {
				$start_date = $tmp['bn_start_date'];
				$end_date = $tmp['bn_end_date'];
				if( ($today > $start_date) && ($today < $end_date)) {
					$mainbanner[$i]['data'] = $tmp;
					$mainbanner[$i]['order'] = $tmp['bn_order'];
					$i++;
				}
			}
		}

		$this->sci->assign('mainbanner' , $mainbanner);

		//get featured product
		$featured_product = $this->mod_product->get_featured_product();
		$this->sci->assign('featured_product' , $featured_product);

		$this->sci->da('index.htm');
	}

}
