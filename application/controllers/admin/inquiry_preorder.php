<?php
class Inquiry_preorder extends MY_Controller {

	var $mod_title = 'Pre Order Request';

	var $table_name = 'inquiry_preorder';
	var $id_field = 'inqp_id';
	var $status_field = 'inqp_status';
	var $entry_field = 'inqp_entry';
	var $stamp_field = 'inqp_stamp';
	var $deletion_field = 'inqp_deletion';
	var $order_field = 'inqp_entry';
	var $order_dir = 'DESC';
	var $label_field = 'inqp_name';

	var $author_field = ' ';
	var $editor_field = ' ';

	var $search_in = array('inqp_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('INQUIRY_PREORDER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
		$this->db->join('product' , 'product.p_id = inquiry_preorder.p_id' , 'left');
		$this->db->join('product_quantity pq' , 'pq.pq_id = inquiry_preorder.pq_id' , 'left');
		$this->db->join('member' , 'member.m_id = inquiry_preorder.m_id' , 'left');
		$this->db->join('product_subcategory psc' , 'product.psc_id = psc.psc_id' , 'left');
		$this->db->join('product_category pc' , 'product.pc_id = pc.pc_id' , 'left');
		$this->db->join('brand br' , 'product.br_id = br.br_id' , 'left');
		$this->db->join('product_type pt' , 'product.pt_id = pt.pt_id' , 'left');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}
	
	
	function index($pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list'); 
		
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
			$this->select_setting(); 
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array(); 
		$maindata = $this->enum_setting($maindata);
		$maindata = $this->iteration_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}
	

	function validation_setting() {
		$this->form_validation->set_rules('inqp_name', 'Name', 'trim|required|xss_clean'); 
	}

	function database_setter() {
		$inqp_name = $this->input->post('inqp_name');
		$this->db->set('inqp_name' , $inqp_name );
 

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['inqp_image_header']['name'] != '') {
			$filename = $this->_upload_image('inqp_image_header');
			$this->db->set('inqp_image_header' , $filename);
		}
		if($_FILES['inqp_image_square']['name'] != '') {
			$filename = $this->_upload_image('inqp_image_square');
			$this->db->set('inqp_image_square' , $filename);
		}
		if($_FILES['inqp_image_square_grayscale']['name'] != '') {
			$filename = $this->_upload_image('inqp_image_square_grayscale');
			$this->db->set('inqp_image_square_grayscale' , $filename);
		}
		if($_FILES['inqp_image_rectangle']['name'] != '') {
			$filename = $this->_upload_image('inqp_image_rectangle');
			$this->db->set('inqp_image_rectangle' , $filename);
		} 
	}


	function pre_add_edit() { 
	}

	function pre_add() {
	}

	function pre_edit($id=0) { 
	}
	
	function view($inqp_id=0) {
		
		$this->join_setting();
		$this->db->where('inqp_id' , $inqp_id);
		$res = $this->db->get('inquiry_preorder');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		
		$this->load->library('form_validation'); 
		 
		$this->sci->da('view.htm');
		 
	}
	
	function reply($inqp_id=0) {  
		$this->load->library('form_validation');
		$this->form_validation->set_rules('to', 'to', 'trim|required|xss_clean');
		$this->form_validation->set_rules('from', 'from', 'trim|required|xss_clean');
		$this->form_validation->set_rules('subject', 'subject', 'trim|required|xss_clean');
		$this->form_validation->set_rules('content', 'Content', 'trim|required|xss_clean');
		
		if($this->form_validation->run() == FALSE) {
			$this->view($inqp_id);
		} else {
			$to = $this->input->post('to');
			$from = $this->input->post('from');
			$subject = $this->input->post('subject');
			$content = $this->input->post('content');

			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from($from, 'Gudang Brands' );
			$this->email->to($to);
			$this->email->subject($subject);
			$this->email->message($content);
	
			$ok = $this->email->send();
			if($ok) {
				$this->db->where('inqp_id' , $inqp_id);
				$this->db->set('inqp_is_replied' , 'Yes');
				$this->db->set('inqp_reply_date' , 'NOW()', FALSE);
				$this->db->set('inqp_reply_content' , $content);
				$this->db->update('inquiry_preorder');
				
				$this->session->set_confirm(1, 'Reply successfully sent !');
			}
			redirect($this->mod_url."view/$inqp_id");
		}
		
		
		
		
	}

}
