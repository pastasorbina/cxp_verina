<?php
class Promo extends MY_Controller {

	var $mod_title = 'Promo';

	var $table_name = 'promo';
	var $id_field = 'pr_id';
	var $status_field = 'pr_status';
	var $entry_field = 'pr_entry';
	var $stamp_field = 'pr_stamp';
	var $deletion_field = 'pr_deletion';
	var $order_field = 'pr_entry';
	var $order_dir = 'DESC';
	var $label_field = 'pr_name';

	var $author_field = 'pr_author';
	var $editor_field = 'pr_editor';

	var $search_in = array('pr_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init(); 
		$this->sci->assign('use_ajax' , FALSE);
		
		$now = date('Y-m-d H:i:s');
		$this->sci->assign('now' , $now);
		$this->config->set_item('global_xss_filtering', FALSE);
	}
	
	function pre_index() {
		$this->session->validate(array('PROMO_VIEW_LIST'), 'admin');
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}
	
	function iteration_setting($maindata=array()) {
		foreach($maindata as $k=>$tmp){
			//get num of products
			$this->db->where('pr_id' , $tmp['pr_id']);
			$numofproduct = $this->db->count_all_results('promo_detail');
			$maindata[$k]['numofproduct'] = $numofproduct;
			
			//get number of freeship area
			$freeship_area = $tmp['pr_freeship_area'];
			$freeship_area = unserialize($freeship_area);
			if(is_array($freeship_area)) {
				$numoffreeship = sizeof($freeship_area);
			} else {
				$numoffreeship = 0;
			} 
			$maindata[$k]['numoffreeship'] = $numoffreeship;
		}
		return $maindata;
	}

	function join_setting() {
		$this->db->join('brand' , 'brand.br_id = promo.pr_br_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('br_id', 'Brand', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pr_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('pr_start_promo', 'Start Promo', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_end_promo', 'End Promo', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_free_shipping', 'Free Shipping', 'trim|xss_clean');
		$this->form_validation->set_rules('list_freeship_area[]', 'Free Shipping Area', 'trim|xss_clean');
		$this->form_validation->set_rules('list_product[]', 'Products', 'trim|xss_clean');
		
		$this->form_validation->set_rules('pr_image_header_brand', '', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_image_square_brand', '', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_image_square_grayscale_brand', '', 'trim|xss_clean');
		$this->form_validation->set_rules('pr_image_rectangle_brand', '', 'trim|xss_clean');
		
		$this->form_validation->set_rules('pr_delivery_guide', 'Delivery Guide', 'trim'); 
	}

	function database_setter($action='add') {
		
		$pr_name = $this->input->post('pr_name');
		$this->db->set('pr_name' , $pr_name );

		$this->db->set('pr_br_id' , $this->input->post('br_id') );
		$this->db->set('pr_start_promo' , $this->input->post('pr_start_promo') );
		$this->db->set('pr_end_promo' , $this->input->post('pr_end_promo') );
		$this->db->set('pr_delivery_guide' , $this->input->post('pr_delivery_guide') );
		

		$pr_free_shipping =  $this->input->post('pr_free_shipping');
		$pr_free_shipping = ($pr_free_shipping=='Yes')?'Yes':'No';
		$this->db->set('pr_free_shipping' , $pr_free_shipping);

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['pr_image_header']['name'] != '') {
			$filename = $this->_upload_image('pr_image_header'); 
		} else {
			$filename = $this->input->post('pr_image_header_brand');
		}
		$this->db->set('pr_image_header' , $filename);
		
		if($_FILES['pr_image_square']['name'] != '') {
			$filename = $this->_upload_image('pr_image_square'); 
		} else {
			$filename = $this->input->post('pr_image_square_brand');
		}
		$this->db->set('pr_image_square' , $filename);
		
		if($_FILES['pr_image_square_grayscale']['name'] != '') {
			$filename = $this->_upload_image('pr_image_square_grayscale'); 
		} else {
			$filename = $this->input->post('pr_image_square_grayscale_brand');
		}
		$this->db->set('pr_image_square_grayscale' , $filename);
		
		if($_FILES['pr_image_rectangle']['name'] != '') {  
			$filename = $this->_upload_image('pr_image_rectangle');
		} else {
			$filename = $this->input->post('pr_image_rectangle_brand');
		}
		$this->db->set('pr_image_rectangle' , $filename);
		

		//free shipping area
		$list_freeship_area = $this->input->post('list_freeship_area');
		$this->db->set('pr_freeship_area' , serialize($list_freeship_area) );
		 
	}
	
	function post_add($insert_id=0, $ok='false') {
		$list_product = $this->input->post('list_product');
		foreach($list_product as $k=>$tmp) {
			$this->db->set('p_id' , $tmp);
			$this->db->set('pr_id' , $insert_id);
			$this->db->insert('promo_detail');
		}
	}
	 
	function post_edit($id=0, $ok='false' ) {
		$list_product = $this->input->post('list_product');
		$this->db->where('pr_id' , $id );
		$this->db->delete('promo_detail');
		foreach($list_product as $k=>$tmp) {
			$this->db->set('p_id' , $tmp);
			$this->db->set('pr_id' , $id);
			$this->db->insert('promo_detail');
		}
	}
	
	function post_delete($id=0) {}


	function pre_add_edit() {
		$this->session->validate(array('PROMO_EDIT'), 'admin');
		$this->load->model('mod_area');
		$area_province = $this->mod_area->get_all_province();
		$this->sci->assign('area_province' , $area_province);
		
		$this->load->model('mod_brand');
		$brands = $this->mod_brand->get_all_brand();
		$this->sci->assign('brands' , $brands);
		
		$this->db->where('pc_status' , 'Active');
		$res = $this->db->get('product_category');
		$categories = $res->result_array();
		$this->sci->assign('categories' , $categories);
		
		$this->db->where('pt_status' , 'Active');
		$res = $this->db->get('product_type');
		$types = $res->result_array();
		$this->sci->assign('types' , $types);
		
		$this->db->where('psc_status' , 'Active');
		$res = $this->db->get('product_subcategory');
		$subcategories = $res->result_array();
		$this->sci->assign('subcategories' , $subcategories);
		
		//get brand_template
		$this->db->where('st_status' , 'Active');
		$res = $this->db->get('shipping_template');
		$template = $res->result_array();
		$this->sci->assign('template' , $template);
		
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
		$this->db->where('pr_id' , $id);
		$res = $this->db->get('promo');
		$brand = $res->row_array();
		$freeship_area = $brand['pr_freeship_area'];
		$freeship_area = unserialize($freeship_area);
		$selected_freeship_area = array();
		if(is_array($freeship_area)) {
			foreach($freeship_area as $k=>$tmp) {
				$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
				$this->db->where('ac_id' , $tmp);
				$res = $this->db->get('area_city');
				$city = $res->row_array();
				$selected_freeship_area[$k] = $city;
			}
		} 
		$this->sci->assign('selected_freeship_area' , $selected_freeship_area);
		
		//get product for this promo
		$this->db->join('product' , 'product.p_id = promo_detail.p_id' , 'left');
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
		$this->db->where('pr_id' , $id);
		$res = $this->db->get('promo_detail');
		$promo_detail = $res->result_array();
		$this->sci->assign('promo_detail' , $promo_detail);
	}
	 

	function ajax_freeship_selected($pr_id=0){
		$this->sci->d('ajax_freeship_selected.htm');
	}

	function ajax_get_city_selection($ap_id=0){
		$this->load->model('mod_area');
		$city = $this->mod_area->get_all_city_by_province($ap_id);
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_city_selection.htm');
	}

	function ajax_add_freeship($ac_id=0){
		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
		$this->db->where('ac_id' , $ac_id);
		$this->db->where('ac_status' , 'Active');
		$res = $this->db->get('area_city');
		$city = $res->row_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_add_freeship.htm');
	}

	function ajax_add_freeship_by_province($ap_id=0){
		$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
		$this->db->where('area_city.ap_id' , $ap_id);
		$this->db->where('ac_status' , 'Active');
		$res = $this->db->get('area_city');
		$city = $res->result_array();
		$this->sci->assign('city' , $city);
		$this->sci->d('ajax_add_freeship_by_province.htm'); 
	}
	
	function ajax_add_freeship_by_template() {
		$st_id = $this->input->post('st_id');
		//get template
		$this->db->where('st_id' , $st_id);
		$res = $this->db->get('shipping_template');
		$template = $res->row_array();
		
		if($template) {
			$st_id_list = unserialize($template['st_ac_id']);
			
			$this->db->join('area_province ap' , 'ap.ap_id = area_city.ap_id' , 'left');
			$this->db->where_in('area_city.ac_id' , $st_id_list);
			$this->db->where('ac_status' , 'Active');
			$this->db->order_by('ac_code' , 'ASC');
			$res = $this->db->get('area_city');
			$city = $res->result_array();
			$this->sci->assign('city' , $city);
			$this->sci->d('ajax_add_freeship_by_template.htm');	
		} 
	}
	
	
	
	function ajax_list_product($br_id=0, $pc_id=0, $pt_id=0, $psc_id=0, $pagelimit='500', $offset=0, $encodedkey='') { 
		$this->sci->assign('br_id' , $br_id);

		//get all brand
		$this->db->where('br_status' , 'Active');
		$this->db->order_by('br_name' , 'asc');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		$orderby = 'p_id';
		$ascdesc = 'DESC';
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby );
		$this->sci->assign('ascdesc' , $ascdesc );
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); } 
			$this->db->order_by($orderby , $ascdesc);
			$this->db->from('product');
			$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
			$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
			$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
			$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
			$this->db->where('p_type' , 'Product');
			$this->db->where('p_status' , 'Active');
			if($br_id !=0) {
				$this->db->where('product.br_id' , $br_id);
			}
			if($pc_id !=0) {
				$this->db->where('product.pc_id' , $pc_id);
			}
			if($pt_id !=0) {
				$this->db->where('product.pt_id' , $pt_id);
			}
			if($psc_id !=0) {
				$this->db->where('product.psc_id' , $psc_id);
			} 
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results('product');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."ajax_list_product/$br_id/$pc_id/$pt_id/$psc_id/". $pagelimit ."/";
		$config['suffix'] = "/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 7;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('product');
		$this->db->flush_cache();
		$maindata = $res->result_array(); 
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() ); 
		$this->sci->d('ajax_list_product.htm');
	}
	
	function ajax_select_product($p_id=0){
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
		$this->db->where('p_id' , $p_id);
		$this->db->where('p_status' , 'Active');
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product);
		$this->sci->d('ajax_select_product.htm');
	}
	
	function ajax_get_brand($br_id=0) {
		$this->db->where('br_id' , $br_id);
		$res = $this->db->get('brand');
		$brand = $res->row_array();
		echo json_encode($brand);
	}

	//function search( ) {
	//	$page = $this->input->post('page');
	//	$searchkey = $this->input->post('searchkey');
	//	$pagelimit = $this->input->post('pagelimit');
	//	$orderby = $this->input->post('orderby');
	//	$offset = $this->input->post('offset');
	//	$br_id = $this->input->post('br_id');
	//	$offset = 0;
	//	$ascdesc = $this->input->post('ascdesc');
	//	$encodedkey = safe_base64_encode($searchkey);
	//	if( !$encodedkey ) { $encodedkey = ''; }
	//	redirect("$page$br_id/$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	//}
	
	
	function ajax_list_select($pagelimit='9', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {  
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		if ($pagelimit == '') $pagelimit = $this->default_pagelimit;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->join_setting();
			$this->where_setting();
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."ajax_list_select/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$maindata = $this->iteration_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->assign('paging_js' , $this->pagination->create_js('#promo_selection') );
		$this->post_index();
		$this->sci->d('ajax_list_select.htm');
	}

}
