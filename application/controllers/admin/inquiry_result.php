<?php
class Inquiry_result extends MY_Controller {

	var $mod_title = 'Inquiries';

	var $table_name = 'inquiry_result';
	var $id_field = 'inqr_id';
	var $status_field = 'inqr_status';
	var $entry_field = 'inqr_entry';
	var $stamp_field = 'inqr_stamp';
	var $deletion_field = 'inqr_deletion';
	var $order_field = 'inqr_entry';
	var $order_dir = 'DESC';
	var $label_field = 'inqr_name';

	var $author_field = 'inqr_author';
	var $editor_field = 'inqr_editor';

	var $search_in = array('inqr_name');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('INQUIRY_RESULT_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}
	
	
	function index($inqt_id=0, $pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		
		$res = $this->db->get('inquiry_type');
		$inquiry_type = $res->result_array();
		$this->sci->assign('inquiry_type' , $inquiry_type);
		
		if($inqt_id == 0) {
			if($inquiry_type) {
				$inqt_id = $inquiry_type[0]['inqt_id'];
			}
		}
		$this->sci->assign('inqt_id' , $inqt_id);
		
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
			if($inqt_id !=0) {
				$this->db->where('inqt_id' , $inqt_id);
			}
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$inqt_id/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
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
		$this->post_index();
		$this->sci->da('index.htm');
	}
	

	function validation_setting() {
		$this->form_validation->set_rules('inqr_name', 'Name', 'trim|required|xss_clean'); 
	}

	function database_setter() {
		$inqr_name = $this->input->post('inqr_name');
		$this->db->set('inqr_name' , $inqr_name );
 

		$this->image_directory = 'userfiles/media/';
		$this->thumb_directory = 'userfiles/media/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['inqr_image_header']['name'] != '') {
			$filename = $this->_upload_image('inqr_image_header');
			$this->db->set('inqr_image_header' , $filename);
		}
		if($_FILES['inqr_image_square']['name'] != '') {
			$filename = $this->_upload_image('inqr_image_square');
			$this->db->set('inqr_image_square' , $filename);
		}
		if($_FILES['inqr_image_square_grayscale']['name'] != '') {
			$filename = $this->_upload_image('inqr_image_square_grayscale');
			$this->db->set('inqr_image_square_grayscale' , $filename);
		}
		if($_FILES['inqr_image_rectangle']['name'] != '') {
			$filename = $this->_upload_image('inqr_image_rectangle');
			$this->db->set('inqr_image_rectangle' , $filename);
		} 
	}


	function pre_add_edit() { 
	}

	function pre_add() {
	}

	function pre_edit($id=0) { 
	}
	
	function view($inqr_id=0) {
		
		$this->db->where('inqr_id' , $inqr_id);
		$res = $this->db->get('inquiry_result');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		
		$this->load->library('form_validation'); 
		
		$this->db->where('inqr_id' , $inqr_id);
		$res = $this->db->get('inquiry_result');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->sci->da('view.htm');
		 
	}
	
	function reply($inqr_id=0) {  
		$this->load->library('form_validation');
		$this->form_validation->set_rules('to', 'to', 'trim|required|xss_clean');
		$this->form_validation->set_rules('from', 'from', 'trim|required|xss_clean');
		$this->form_validation->set_rules('subject', 'subject', 'trim|required|xss_clean');
		$this->form_validation->set_rules('content', 'Content', 'trim|required|xss_clean');
		
		if($this->form_validation->run() == FALSE) {
			$this->view($inqr_id);
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
				$this->db->where('inqr_id' , $inqr_id);
				$this->db->set('inqr_is_replied' , 'Yes');
				$this->db->set('inqr_reply_date' , 'NOW()', FALSE);
				$this->db->set('inqr_reply_content' , $content);
				$this->db->update('inquiry_result');
				
				$this->session->set_confirm(1, 'Reply successfully sent !');
			}
			redirect($this->mod_url."view/$inqr_id");
		}
		
		
		
		
	}

}
