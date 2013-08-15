<?php
class Item_stock extends MY_Controller {

	var $mod_title = 'Manage Stock';
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
		$this->session->validate(array('ITEM_STOCK_MANAGE'), 'admin');

		$this->sci->assign('use_ajax' , FALSE);

		$this->load->model('mod_product');
		$this->load->model('mod_stock');

		$this->product_type = $this->mod_product_type->get_list('*', array('pt_status'=>'Active'));
		$this->sci->assign('product_type' , $this->product_type);

		$this->product_category = $this->mod_product_category->get_list('*', array('pc_status'=>'Active'));
		$this->sci->assign('product_category' , $this->product_category);

		//$this->brand = $this->mod_brand->get_list('*', array('br_status'=>'Active'));
		//$this->sci->assign('brand' , $this->brand);
		
		
		$this->db->where('pt_status' , 'Active');
		$this->db->order_by('pt_name' , 'asc');
		$res = $this->db->get('product_type');
		$this->product_type = $res->result_array();
		$this->sci->assign('product_type' , $this->product_type);
 
		$this->db->where('pc_status' , 'Active');
		$this->db->order_by('pc_name' , 'asc');
		$res = $this->db->get('product_category');
		$product_category = $res->result_array();
		$this->sci->assign('product_category' , $product_category); 

		$this->db->where('psc_status' , 'Active');
		$this->db->order_by('psc_name' , 'asc');
		$res = $this->db->get('product_subcategory');
		$product_subcategory = $res->result_array();
		$this->sci->assign('product_subcategory' , $product_subcategory);
		
		//get all brand
		$this->db->where('br_status' , 'Active');
		$this->db->order_by('br_name' , 'asc');
		$res = $this->db->get('brand');
		$brands = $res->result_array();
		$this->sci->assign('brands' , $brands);

		$this->userinfo = $this->session->get_userinfo();
	}

	function index($p_id=0, $br_id=0, $pt_id=0, $pc_id=0, $psc_id=0, $pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->sci->assign('p_id' , $p_id);
		$this->sci->assign('br_id' , $br_id);
		$this->sci->assign('pt_id' , $pt_id);
		$this->sci->assign('pc_id' , $pc_id);
		$this->sci->assign('psc_id' , $psc_id); 
		$this->session->set_bread('list-item_stock');
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
			if($encodedkey != ''){
				$searchkey = safe_base64_decode($encodedkey);
				$this->search_in = array('p_name','p_code');
				if(!empty($this->search_in)) {
					foreach($this->search_in as $k=>$tmp) { $this->db->or_like($tmp, $searchkey); }
				} else {
					$this->search_setting($searchkey);
				}
				$this->sci->assign('searchkey' , $searchkey);
			}
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by('pq_ordering' , 'ASC');
			$this->db->from("product_quantity");
			$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
			$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
			$this->db->where('pq_status' ,"Active");
			$this->db->where('p_status' ,"Active");
			$this->db->where('br_status' ,"Active");
			$this->db->where('p_type' , 'Product');
			if($p_id !=0 ) {
				$this->db->where('product_quantity.p_id' , $p_id);
			}
			if($br_id !=0) {
				$this->db->where('p.br_id' , $br_id);
			}
			if($pt_id !=0) {
				$this->db->where('p.pt_id' , $pt_id);
			}
			if($pc_id !=0) {
				$this->db->where('p.pc_id' , $pc_id);
			}
			if($psc_id !=0) {
				$this->db->where('p.psc_id' , $psc_id);
			}
		$this->db->stop_cache();
		/*--cache-stop--*/
		$data_total = $this->db->count_all_results();
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$p_id/$br_id/$pt_id/$pc_id/$psc_id/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $data_total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 10;
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
		$res = $this->db->get();
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();

		$this->sci->da('index.htm');
	}
	
	function search( ) { 
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$p_id = $this->input->post('p_id');
		$br_id = $this->input->post('br_id');
		$pt_id = $this->input->post('pt_id');
		$pc_id = $this->input->post('pc_id');
		$psc_id = $this->input->post('psc_id');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$p_id/$br_id/$pt_id/$pc_id/$psc_id/$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}

	function stock_change($pq_id=0, $action="add") {
		$this->sci->assign('pq_id' , $pq_id);
		$this->sci->assign('action' , $action);
		$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('pq_id' , $pq_id);
		$res = $this->db->get('product_quantity');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->load->library('form_validation');
		$this->sci->d('stock_add.htm');
	}

	function stock_change_submit() {
		$action = $this->input->post('action');
		$pq_id = $this->input->post('pq_id');
		$p_id = $this->input->post('p_id');
		$change = $this->input->post('quantity');
		$note = $this->input->post('note');

		$this->db->join('product p' , 'p.p_id = product_quantity.p_id' , 'left');
		$this->db->join('brand br' , 'br.br_id = p.br_id' , 'left');
		$this->db->where('pq_id' , $pq_id);
		$res = $this->db->get('product_quantity');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$current_quantity = $data['pq_quantity'];

		$this->load->library('form_validation');
		$this->form_validation->set_rules('quantity', 'Quantity', 'trim|required|xss_clean');
		$this->form_validation->set_rules('notes', 'Notes', 'trim|xss_clean');

		$ret = array();
		if($this->form_validation->run() == FALSE) {
			$ret['status'] = 'error';
			$ret['msg'] = strip_tags(validation_errors());
			echo json_encode($ret);
		} else {
			//if this is a stock_out, and quantity is more than current stock
			if($action=='stock_out' && ($change > $current_quantity) ) {
				$ret['status'] = 'error';
				$ret['msg'] = "cannot substract more than current stock";
				echo json_encode($ret); return false;
			}

			$this->db->trans_start();
				//perubahan stock
				$config = array();
				$config['id'] = $pq_id;
				$config['change'] = $change;
				$config['note'] = $note;
				$config['action'] = $action;
				$config['u_id'] = $this->userinfo['u_id'];
				if($action=='stock_in') {
					$this->mod_stock->stock_in($config);
				} elseif($action=='stock_out') {
					$this->mod_stock->stock_out($config);
				}
			$this->db->trans_complete();
			if($this->db->trans_status() === FALSE) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}

			$ret['status'] = 'ok';
			$ret['msg'] = "";
			echo json_encode($ret);
		}
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
	}

	function where_setting() {

	}

	function validation_setting() {
	}

	function database_setter() {
	}

	function pre_add_edit() {

	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function delete($id=0) {
	}
	
	function ajax_product_option() {
		$br_id = $this->input->post('br_id');
		$this->sci->d('ajax_product_option.htm');
	}
}
