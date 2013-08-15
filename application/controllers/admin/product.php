<?php
class Product extends MY_Controller {

	var $mod_title = 'Manage Product';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'product';
	var $id_field = 'p_id';
	var $status_field = 'p_status';
	var $entry_field = 'p_entry';
	var $stamp_field = 'p_stamp';
	var $deletion_field = 'p_deletion';
	var $order_field = 'p_entry';

	var $author_field = 'p_author';
	var $editor_field = 'p_editor';

	var $search_in = array('p_name','p_code');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";

	var $default_pagelimit = 40;


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();

		$this->sci->assign('use_ajax' , FALSE);

		$this->load->model('mod_product');

		$this->userinfo = $this->session->get_userinfo();

		$this->db->where('pt_status' , 'Active');
		$this->db->order_by('pt_name' , 'asc');
		$res = $this->db->get('product_type');
		$this->product_type = $res->result_array();
		$this->sci->assign('product_type' , $this->product_type);

		//$this->product_category = $this->mod_product_category->get_list('*', array('pc_status'=>'Active'));
		$this->db->where('pc_status' , 'Active');
		$this->db->order_by('pc_name' , 'asc');
		$res = $this->db->get('product_category');
		$product_category = $res->result_array();
		$this->sci->assign('product_category' , $product_category);
		//print_r($product_category);

		$this->db->where('psc_status' , 'Active');
		$this->db->order_by('psc_name' , 'asc');
		$res = $this->db->get('product_subcategory');
		$product_subcategory = $res->result_array();
		$this->sci->assign('product_subcategory' , $product_subcategory);


	}


	function index($br_id=0,$pt_id = 0, $pc_id=0, $psc_id=0,  $pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		//$this->session->validate(array('PRODUCT_VIEW_LIST'), 'admin');
		$this->session->set_bread('list');
		$this->sci->assign('br_id' , $br_id);
		$this->sci->assign('pt_id' , $pt_id);
		$this->sci->assign('pc_id' , $pc_id);
		$this->sci->assign('psc_id' , $psc_id);

		//get all brand
		$this->db->where('br_status' , 'Active');
		$this->db->order_by('br_name' , 'asc');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		$this->pre_index();
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
			//
			if($br_id !=0) {
				$this->db->where('product.br_id' , $br_id);
			}
			if($pt_id !=0) {
				$this->db->where('product.pt_id' , $pt_id);
			}
			if($pc_id !=0) {
				$this->db->where('product.pc_id' , $pc_id);
			}
			if($psc_id !=0) {
				$this->db->where('product.psc_id' , $psc_id);
			}
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$data_total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$br_id/$pt_id/$pc_id/$psc_id/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $data_total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 9;
		$this->pagination->initialize($config);

		$this->sci->assign('data_total' , $data_total);

		$page_total = 0; $page_current = 0;
		if($data_total > 0) {
			$page_total = ceil($data_total/$pagelimit);
			$page_current = ceil( ($offset/($data_total / $page_total))+0.0000001 ) ;
		}
		$this->sci->assign('page_total' , $page_total);
		$this->sci->assign('page_current' , $page_current);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);

		foreach($maindata as $k=>$tmp){
			$this->db->where('pq_status' , 'Active');
			$this->db->where('p_id' , $tmp['p_id']);
			$res = $this->db->get('product_quantity');
			$quantity = $res->result_array();
			$maindata[$k]['sizes'] = $quantity;
			$maindata[$k]['total_sizes'] = sizeof($quantity);
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function search( ) {
		//print_r($_POST);
		//die();
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$br_id = $this->input->post('br_id');
		$pt_id = $this->input->post('pt_id');
		$pc_id = $this->input->post('pc_id');
		$psc_id = $this->input->post('psc_id');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$br_id/$pt_id/$pc_id/$psc_id/$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
		$this->db->where('p_type' , 'Product');
	}

	function validation_setting() {
		$this->form_validation->set_rules('p_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('p_code', 'Code', 'trim|xss_clean');
		$this->form_validation->set_rules('pc_id', 'Category', 'trim|xss_clean');
		$this->form_validation->set_rules('pt_id', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('br_id', 'Brand', 'trim|xss_clean');
		$this->form_validation->set_rules('p_order', 'Ordering', 'trim|xss_clean');
		$this->form_validation->set_rules('p_price', 'Price', 'trim|xss_clean');
		$this->form_validation->set_rules('p_discount_price', 'Discounted Price', 'trim|xss_clean');
		$this->form_validation->set_rules('p_weight', 'Weight', 'trim|xss_clean');
		$this->form_validation->set_rules('p_description', 'Description', 'trim');
	}

	function database_setter() {
		$p_is_featured = $this->input->post('p_is_featured');
		$p_is_featured = ($p_is_featured=='Yes')?$p_is_featured:'No';

		$this->db->set('p_is_featured' , $p_is_featured );
		$this->db->set('pc_id' , $this->input->post('pc_id'));
		$this->db->set('psc_id' , $this->input->post('psc_id'));
		$this->db->set('pt_id' , $this->input->post('pt_id'));
		$this->db->set('br_id' , $this->input->post('br_id'));
		$this->db->set('p_order' , $this->input->post('p_order'));
		$this->db->set('p_name' , $this->input->post('p_name') );
		$this->db->set('p_code' , $this->input->post('p_code') );
		$this->db->set('p_price' , $this->input->post('p_price') );
		$this->db->set('p_discount_price' , $this->input->post('p_discount_price') );
		$this->db->set('p_description' , $this->input->post('p_description') );
		$this->db->set('p_weight' , $this->input->post('p_weight') );

		if($_FILES['p_image1']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image1');
			$this->db->set('p_image1' , $filename);
		}
		if($_FILES['p_image2']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image2');
			$this->db->set('p_image2' , $filename);
		}
		if($_FILES['p_image3']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image3');
			$this->db->set('p_image3' , $filename);
		}
		if($_FILES['p_image4']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image4');
			$this->db->set('p_image4' , $filename);
		}
		if($_FILES['p_image5']['name'] != '' ) {
			$filename = $this->_upload_product_image('p_image5');
			$this->db->set('p_image5' , $filename);
		}
	}


	function _upload_product_image( $fieldname = '', $crop = FALSE ) {
		$l_dir = 'userfiles/product/l/';
		$m_dir = 'userfiles/product/m/';
		$s_dir = 'userfiles/product/s/';
		$m_width = 300; $m_height = 300;
		$s_width = 50; $s_height = 50;
		$filename = FALSE;
		if (!empty($_FILES)) {
			if ($_FILES[$fieldname]['tmp_name']) {
				$code = uniqid();
				$realFile = $_FILES[$fieldname]['name'];
				$extension = end(explode(".", $realFile));
				$tempFile = $_FILES[$fieldname]['tmp_name'];
				$filename = $code . '.' . $extension;
				$l_file =  $l_dir . $filename;
				$m_file =  $m_dir . $filename;
				$s_file =  $s_dir . $filename;
				move_uploaded_file($tempFile, $l_file);
				@chmod($l_file, 0755);

				// make medium
				$config['image_library'] = 'gd2';
				$config['source_image'] = $l_file;
				$config['new_image'] = $m_file;
				$config['width'] = $m_width;
				$config['height'] = $m_height;
				$config['maintain_ratio'] = FALSE;
				$this->load->library('image_lib');
				$this->image_lib->initialize($config);
				$this->image_lib->resize();
				$this->image_lib->clear();
				@chmod($m_file, 0755);

				// make small
				$config['image_library'] = 'gd2';
				$config['source_image'] = $l_file;
				$config['new_image'] = $s_file;
				$config['width'] = $s_width;
				$config['height'] = $s_height;
				$config['maintain_ratio'] = FALSE;
				$this->load->library('image_lib');
				$this->image_lib->initialize($config);
				$this->image_lib->resize();
				$this->image_lib->clear();
				@chmod($s_file, 0755);
			}
		}
		return $filename;
	}


	function pre_add_edit() {
		//$this->session->validate(array('PRODUCT_EDIT'), 'admin');
		$this->db->where('br_status' , 'Active');
		$this->db->order_by('br_name' , 'ASC');
		$res = $this->db->get('brand');
		$brand = $res->result_array();
		$this->sci->assign('brand' , $brand);
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
		$this->db->where('pq_status' , 'Active');
		$this->db->where('p_id' , $id);
		$res = $this->db->get('product_quantity');
		$product_quantity = $res->result_array();
		$this->sci->assign('product_quantity' , $product_quantity);
	}

	function add() {
		$this->sci->assign('ajax_action' , 'add');
		$this->pre_add_edit();
		$this->pre_add();

		$this->load->library('form_validation');
		$this->validation_setting('add');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('add.htm');
		}else{
			$this->database_setter('add');
			$this->db->set($this->entry_field , date('Y-m-d H:i:s') );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();
			$this->post_add($insert_id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				redirect($this->mod_url."view/$insert_id" );
			}
		}
	}

	function ajax_edit( $id=0 ) {
		$this->sci->assign('ajax_action' , 'edit');
		$this->pre_add_edit();
		$this->join_setting();
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->pre_edit($id);

		$this->load->library('form_validation');
		$this->validation_setting('edit');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->d('load_edit.htm');
		}else{
			$this->database_setter('edit');
			$this->db->where($this->id_field , $id);
			$ok = $this->db->update($this->table_name);
			$this->post_edit($id);
			if(!$ok) {
				$data['status'] = 'error';
				$data['msg'] = validation_errors();
			} else {
				$data['status'] = 'ok';
			}
			echo json_encode($data);
		}
	}

	function submit_edit_product(){
		$id = $this->input->post('current_id');
		$this->load->library('form_validation');
		$this->validation_setting('edit');

		$this->database_setter('edit');
		$this->db->where($this->id_field , $id);
		$ok = $this->db->update($this->table_name);
		$this->post_edit($id);
		if(!$ok) {
			$data['status'] = 'error';
			$data['msg'] = validation_errors();
		} else {
			$data['status'] = 'ok';
		}
		echo json_encode($data);
	}

	function edit( $id=0 ) {
		$this->sci->assign('ajax_action' , 'edit');
		$this->pre_add_edit();
		$this->join_setting();
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->pre_edit($id);

		$this->load->library('form_validation');
		$this->validation_setting('edit');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da($this->template_edit);
		}else{
			$this->database_setter('edit');
			$this->db->where($this->id_field , $id);
			$ok = $this->db->update($this->table_name);
			$this->post_edit($id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url."edit/$id");
			} else {
				$this->session->set_confirm(1);
				redirect($this->mod_url."view/$id" );
			}
		}
	}

	function delete($id=0) {
		//$this->session->validate(array('PRODUCT_DELETE'), 'admin');
		$this->change_status($id, 'Deleted');
	}

	function add_quantity() {
		//$this->session->validate(array('PRODUCT_EDIT'), 'admin');
		$p_id = $this->input->post('p_id');
		$pq_size = $this->input->post('pq_size');
		$pq_quantity = $this->input->post('pq_quantity');
		$userinfo = $this->session->get_userinfo();

		$this->load->library('form_validation');
		$this->form_validation->set_rules('pq_size', 'Size', 'required|trim|xss_clean');
		$this->form_validation->set_rules('pq_quantity', 'Quantity', 'required|trim|xss_clean');
		$this->form_validation->set_rules('p_id', 'p_id', 'required|trim|xss_clean');

		if($this->form_validation->run() == FALSE) {
		} else {
			$this->db->set('p_id' , $p_id);
			$this->db->set('pq_size' , $pq_size);
			$this->db->set('pq_quantity' , $pq_quantity);
			$this->db->set('pq_author' , $userinfo['u_id']);
			$this->db->set('pq_entry' , date('Y-m-d H:i:s') );
			$ok = $this->db->insert('product_quantity');
		}
		redirect($this->mod_url."edit/$p_id");
	}

	function update_quantity() {
		//$this->session->validate(array('PRODUCT_EDIT'), 'admin');
		$p_id = $this->input->post('p_id');
		$pq_id = $this->input->post('pq_id');
		$pq_size = $this->input->post('pq_size');
		$pq_quantity = $this->input->post('pq_quantity');
		$userinfo = $this->session->get_userinfo();

		$this->load->library('form_validation');
		$this->form_validation->set_rules('pq_id', 'pq_id', 'xss_clean');
		$this->form_validation->set_rules('pq_size', 'Size', 'xss_clean');
		$this->form_validation->set_rules('pq_quantity', 'Quantity', 'xss_clean');
		$this->form_validation->set_rules('p_id', 'p_id', 'required|xss_clean');

		if($this->form_validation->run() == FALSE) {
		} else {
			foreach($pq_id as $k=>$tmp){
				$this->db->set('pq_quantity' , $pq_quantity[$k]);
				$this->db->set('pq_size' , $pq_size[$k]);
				$this->db->where('pq_id' , $tmp);
				$this->db->update('product_quantity');
			}
		}
		redirect($this->mod_url."edit/$p_id");
	}


	function fix_all_image() {
		$to_change = array('p_image1', 'p_image2', 'p_image3', 'p_image4', 'p_image5');
		$res = $this->db->get('product');
		$product = $res->result_array();

		foreach($product as $k=>$tmp) {
			$source_dir = 'userfiles/media/';
			$l_dir = 'userfiles/product/l/';
			$m_dir = 'userfiles/product/m/';
			$s_dir = 'userfiles/product/s/';
			$m_width = 300; $m_height = 300;
			$s_width = 50; $s_height = 50;
			$filename = FALSE;
			print 'moving p_id:'.$tmp['p_id']."<br>";

			foreach($to_change as $k2=>$tmp2) {
				if ($tmp[$tmp2]) {
					print "processing: ".$tmp2.':'.$tmp[$tmp2]."<br>";

					$filename = $tmp[$tmp2];
					$source_file = $source_dir . $filename;
					$l_file =  $l_dir . $filename;
					$m_file =  $m_dir . $filename;
					$s_file =  $s_dir . $filename;
					$copy_ok = copy($source_file, $l_file);
					if($copy_ok) {
						print "copy to L[OK]<br>";
						$del_ok = @unlink($source_file);
						if($del_ok) {
							print "del source[OK]<br>";
						}
						@chmod($l_file, 0755);

						// make medium
						$config['image_library'] = 'gd2';
						$config['source_image'] = $l_file;
						$config['new_image'] = $m_file;
						$config['width'] = $m_width;
						$config['height'] = $m_height;
						$config['maintain_ratio'] = FALSE;
						$this->load->library('image_lib');
						$this->image_lib->initialize($config);
						$m_ok = $this->image_lib->resize();
						if($m_ok) {
							print "make M[OK]<br>";
							$this->image_lib->clear();
							@chmod($m_file, 0755);
						}

						// make small
						$config['image_library'] = 'gd2';
						$config['source_image'] = $l_file;
						$config['new_image'] = $s_file;
						$config['width'] = $s_width;
						$config['height'] = $s_height;
						$config['maintain_ratio'] = FALSE;
						$this->load->library('image_lib');
						$this->image_lib->initialize($config);
						$s_ok = $this->image_lib->resize();
						if($s_ok) {
							print "make S[OK]<br>";
							$this->image_lib->clear();
							@chmod($s_file, 0755);
						}

					}
				}

			}
		print "<hr>";
		}
	}

	function view($p_id){
		//$this->session->validate(array('PRODUCT_VIEW_DETAIL'), 'admin');
		$this->session->set_bread('view-product');
		$this->join_setting();
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product);

		//get stock
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product_quantity');
		$product_quantity = $res->result_array();
		$this->sci->assign('product_quantity' , $product_quantity);

		$this->sci->da('view.htm');
	}

	function load_view($p_id=0){
		$this->join_setting();
		$this->db->where('p_id' , $p_id);
		$res = $this->db->get('product');
		$product = $res->row_array();
		$this->sci->assign('product' , $product);
		$this->sci->d('load_view.htm');
	}




	function ajax_list_size($p_id=0, $status='Active'){
		$this->sci->assign('pq_status' , $status);
		$this->sci->assign('p_id' , $p_id);
		$this->db->where('pq_status' , $status);
		$this->db->where('p_id' , $p_id);
		$this->db->order_by('pq_ordering' , 'asc');
		$res = $this->db->get('product_quantity');
		$product_quantity = $res->result_array();
		$this->sci->assign('product_quantity' , $product_quantity);
		$this->sci->d('size_list.htm');
	}

	function ajax_add_size($p_id=0) {
		$this->sci->assign('p_id' , $p_id);
		$this->sci->assign('action' , 'add');
		$this->load->library('form_validation');
		if ($this->form_validation->run() == FALSE) {
			$this->sci->d('size_add.htm');
		}
	}

	function ajax_edit_size($pq_id=0) {
		$this->sci->assign('pq_id' , $pq_id);
		$this->sci->assign('action' , 'edit');

		$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
		$this->db->where('pq_status' , 'Active');
		$this->db->where('pq_id' , $pq_id);
		$res = $this->db->get('product_quantity');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->sci->assign('p_id' , $data['p_id']);
		$this->load->library('form_validation');

		if ($this->form_validation->run() == FALSE) {
			$this->sci->d('size_add.htm');
		}
	}

	function ajax_addedit_size_submit($p_id=0) {
		$this->sci->assign('p_id' , $p_id);
		$this->sci->assign('action' , 'add');

		$ret = array();
		$this->load->library('form_validation');

		$this->form_validation->set_rules('pq_size', 'Size', 'required|trim|xss_clean');
		$this->form_validation->set_rules('quantity', 'Size', 'trim|xss_clean');
		$this->form_validation->set_rules('pq_ordering', 'Size', 'trim|xss_clean');

		if ($this->form_validation->run() == FALSE) {
			$ret['status'] = 'error';
			$ret['msg'] = strip_tags(validation_errors());
		} else {
			$action = $this->input->post('action');
			$pq_size = $this->input->post('pq_size');
			$p_id= $this->input->post('p_id');
			$pq_id= $this->input->post('pq_id');
			$pq_ordering = $this->input->post('pq_ordering');

			if($action == 'add') {
				$this->db->set('pq_size' , $pq_size);
				$this->db->set('pq_entry' , 'NOW()', FALSE);
				$this->db->set('pq_author' , $this->userinfo['u_id']);
				$this->db->set('p_id' , $p_id);
				$this->db->set('pq_ordering' , $pq_ordering);
				$this->db->insert('product_quantity');
				$insert_id = $this->db->insert_id();
				//add initial stock
				$config = array();
				$config['id'] = $insert_id;
				$config['change'] = $this->input->post('quantity');
				$config['note'] = 'initial stock';
				$config['action'] = 'stock_in';
				$config['u_id'] = $this->userinfo['u_id'];
				$this->mod_stock->stock_in($config);

			} elseif($action == 'edit') {
				$this->db->set('pq_size' , $pq_size);
				$this->db->where('pq_id' , $pq_id);
				$this->db->set('pq_ordering' , $pq_ordering);
				$this->db->update('product_quantity');
			}

			$ret['status'] = 'ok';
			$ret['p_id'] = $p_id;
		}
		echo json_encode($ret);
	}

	function ajax_delete_size($pq_id=0){
		$this->db->where('pq_id' , $pq_id);
		$this->db->where('pq_status' , 'Active');
		$this->db->set('pq_status' , 'Deleted');
		$this->db->set('pq_deletion' , 'NOW()', FALSE);
		$this->db->set('pq_editor' , $this->userinfo['u_id']);
		$this->db->update('product_quantity');
	}

	function ajax_restore_size($pq_id=0){
		$this->db->where('pq_id' , $pq_id);
		$this->db->where('pq_status' , 'Deleted');
		$this->db->set('pq_status' , 'Active');
		$this->db->set('pq_deletion' , 'NOW()', FALSE);
		$this->db->set('pq_editor' , $this->userinfo['u_id']);
		$this->db->update('product_quantity');
	}


	/****
	 * IMAGES *
	 */

	public function _img_view_list($p_id=0){
		$this->sci->d('image/_view_list.htm');
	}


	/****
	 * TAGS *
	 */
	public function search_tags_json(){
		$term = $this->input->post('term');
		$p_id = $this->input->post('p_id');

		$this->db->like('pt_name' , $term);
		$res = $this->db->get('product_tag');
		$tags = $res->result_array();

		$data['data'] = $tags;
		$data['status'] = 'ok';
		echo json_encode($data);
	}

	public function list_product_to_tag($p_id=0){
		$this->db->join('product_tag pt' , 'pt.pt_id = product_to_tag.pt_id' , 'left');
		$this->db->where('p_id' , $p_id);
		$this->db->order_by('ptt_id' , 'DESC');
		$res = $this->db->get('product_to_tag');
		$product_to_tag = $res->result_array();
		$this->sci->assign('product_to_tag' , $product_to_tag);
		$this->sci->d('tags/list_product_to_tag.htm');
	}

	public function _register_new_tag($pt_name=''){
		$this->db->set('pt_name' , $pt_name);
		$this->db->set('pt_slug' , url_title($pt_name, '_', TRUE) );
		$this->db->set('pt_entry' , NOW(), FALSE);
		$this->db->insert('product_tag');
		return $this->db->insert_id();
	}

	public function submit_tag_to_product(){
		$term = $this->input->post('term');
		$p_id = $this->input->post('p_id');

		if($term == '') {
			$data['status'] = 'error';
			$data['msg'] = 'No Tag Specified !';
			echo json_encode($data);
			exit();
		}



		$this->db->where('pt_name' , $term);
		$res = $this->db->get('product_tag');
		$product_tag = $res->row_array();
		if($product_tag){

			$pt_id = $product_tag['pt_id'];

			$this->db->where('pt_id' , $pt_id);
			$this->db->where('p_id' , $p_id);
			$res = $this->db->get('product_to_tag');
			$product_to_tag = $res->row_array();
			if($product_to_tag) {
				$data['status'] = 'error';
				$data['msg'] = 'Tag already registered !';
				echo json_encode($data);
				exit();
			}

		} else {
			$pt_id = $this->_register_new_tag($term);
		}

		$this->db->set('pt_id' , $pt_id);
		$this->db->set('p_id' , $p_id);
		$this->db->insert('product_to_tag');
		$data['status'] = 'ok';
		$data['msg'] = 'Tag Added !';
		echo json_encode($data);
	}

	public function remove_product_to_tag($pt_id=0, $p_id=0){
		$this->db->where('pt_id' , $pt_id);
		$this->db->where('p_id' , $p_id);
		$this->db->delete('product_to_tag');
		$data['status'] = 'ok';
		$data['msg'] = 'Tag Removed !';
		echo json_encode($data);
	}




}
