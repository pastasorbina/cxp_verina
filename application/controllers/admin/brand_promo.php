<?php
class Brand_promo extends MY_Controller {

	var $mod_title = 'Brand Promo';

	var $table_name = 'brand_promo';
	var $id_field = 'brp_id';
	var $status_field = 'brp_status';
	var $entry_field = 'brp_entry';
	var $stamp_field = 'brp_stamp';
	var $deletion_field = 'brp_deletion';
	var $order_field = 'brp_entry';
	var $order_dir = 'DESC';
	var $label_field = 'brp_name';

	var $author_field = 'brp_author';
	var $editor_field = 'brp_editor';

	var $search_in = array('brp_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('brand br' , 'br.br_id = brand_promo.brp_br_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('brp_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('brp_start_promo', 'Start Promo', 'trim|xss_clean');
		$this->form_validation->set_rules('brp_end_promo', 'End Promo', 'trim|xss_clean');
		$this->form_validation->set_rules('brp_free_shipping', 'Free Shipping', 'trim|xss_clean');
		$this->form_validation->set_rules('list_freeship_area[]', 'Free Shipping Area', 'trim|xss_clean');
	}

	function database_setter() {
		$brp_name = $this->input->post('brp_name');
		$this->db->set('brp_name' , $brp_name );

		$this->db->set('brp_start_promo' , $this->input->post('brp_start_promo') );
		$this->db->set('brp_end_promo' , $this->input->post('brp_end_promo') );

		$brp_free_shipping =  $this->input->post('brp_free_shipping');
		$brp_free_shipping = ($brp_free_shipping=='Yes')?'Yes':'No';
		$this->db->set('brp_free_shipping' , $brp_free_shipping);

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['brp_image_header']['name'] != '') {
			$filename = $this->_upload_image('brp_image_header');
			$this->db->set('brp_image_header' , $filename);
		}
		if($_FILES['brp_image_square']['name'] != '') {
			$filename = $this->_upload_image('brp_image_square');
			$this->db->set('brp_image_square' , $filename);
		}
		if($_FILES['brp_image_square_grayscale']['name'] != '') {
			$filename = $this->_upload_image('brp_image_square_grayscale');
			$this->db->set('brp_image_square_grayscale' , $filename);
		}
		if($_FILES['brp_image_rectangle']['name'] != '') {
			$filename = $this->_upload_image('brp_image_rectangle');
			$this->db->set('brp_image_rectangle' , $filename);
		}

		//free shipping area
		$list_freeship_area = $this->input->post('list_freeship_area');
		$this->db->set('brp_freeship_area' , serialize($list_freeship_area) );
	}


	function pre_add_edit() {
		$this->load->model('mod_area');
		$area_province = $this->mod_area->get_all_province();
		$this->sci->assign('area_province' , $area_province);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
		$this->db->where('brp_id' , $id);
		$res = $this->db->get('brand_promo');
		$promo = $res->row_array();
		$freeship_area = $promo['brp_freeship_area'];
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
	}

	function ajax_freeship_selected($brp_id=0){
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

}
