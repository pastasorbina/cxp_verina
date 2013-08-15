<?php
class Member extends MY_Controller {

	var $mod_title = 'Members';

	var $table_name = 'member';
	var $id_field = 'm_id';
	var $status_field = 'm_status';
	var $entry_field = 'm_entry';
	var $stamp_field = 'm_stamp';
	var $deletion_field = 'm_deletion';
	var $order_field = 'm_entry';
	var $order_dir = 'DESC';
	var $label_field = 'm_email';

	var $author_field = 'm_author';
	var $editor_field = 'm_editor';

	var $search_in = array('m_email', 'm_firstname','m_lastname');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		//$this->session->validate(array('MEMBER_VIEW_LIST'), 'admin');
		$this->sci->assign('use_ajax' , TRUE);

	}

	function index($m_status='Active', $filter="any", $pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
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
		$this->sci->assign('m_status' , $m_status);
		$this->sci->assign('filter' , $filter);
		/*--cache-start--*/
		$this->db->start_cache();
			$this->db->select('* ');

			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->join_setting();
			$this->where_setting();
			$this->db->where('m_status' , $m_status);

			switch($filter) {
				case 'today' :
					$this->db->where('DATE(m_last_login)' , date('Y-m-d') );
					break;
				case 'any'	: break;
				default 	: break;
			}

		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$m_status/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 7;
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

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function iteration_setting($maindata=array()) {
		foreach( $maindata as $k=>$tmp ) {
			$this->db->where('m_id' , $tmp['m_referal_id'] );
			$res = $this->db->get('member');
			$referal = $res->row_array();
			$maindata[$k]['referal'] = $referal;
		}
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		//$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('m_name', 'Name', 'trim|required|xss_clean');
	}

	function database_setter() {
		$m_name = $this->input->post('m_name');
		$this->db->set('m_name' , $m_name );

		$this->image_directory = 'userfiles/product_category/';
		$this->thumb_directory = 'userfiles/product_category/thumb/';
		$this->thumb_width = 125;
		$this->thumb_height = 125;

		if($_FILES['m_image']['name'] != '') {
			$filename = $this->_upload_image('m_image');
			$this->db->set('m_image' , $filename);
		}
	}


	function pre_add_edit() {
		show_error('function is disabled');
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function view($m_id=0) {
		$this->db->where('m_id' , $m_id);
		$res = $this->db->get('member');
		$member = $res->row_array();

		$this->sci->assign('member' , $member);

		$fb_info = array();

		if($member['fb_raw_json'] != '') {
			$fb_info = json_decode($member['fb_raw_json']);
			$this->sci->assign('fb_info' , $fb_info);
			//print_r($fb_info);
		}

		if($member['m_referal_id'] != '0') {
			$this->db->where('m_id' , $member['m_referal_id']);
			$res = $this->db->get('member');
			$referal = $res->row_array();
			$this->sci->assign('referal' , $referal);
		}

		//get address
		$this->db->join('area_city ac' , 'ac.ac_id = member_address.ac_id' , 'left');
		$this->db->join('area_province ap' , 'ap.ap_id = member_address.ap_id' , 'left');
		$this->db->where('m_id' , $m_id);
		$this->db->where('madr_status' , 'Active');
		$res = $this->db->get('member_address');
		$addresses = $res->result_array();
		$this->sci->assign('addresses' , $addresses);

		//get children
		$this->db->where('m_status' , 'Active');
		$this->db->where('m_referal_id' , $m_id);
		$res = $this->db->get('member');
		$children = $res->result_array();
		$this->sci->assign('children' , $children);

		$this->sci->da('view.htm');
	}

	function send_activation($m_id) {
		$this->db->where('m_id' , $m_id);
		$res = $this->db->get('member');
		$member = $res->row_array();

		//send email
		$this->load->library('email');
		$config['mailtype'] = 'html';
		$config['charset'] = 'iso-8859-1';
		$this->email->initialize($config);
		$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
		$this->email->to($member['m_login']);

		$this->email->subject( 'Activate Your Account');
		$this->sci->assign('fullname' , $member['m_firstname'].' '.$member['m_lastname']);
		$this->sci->assign('key' , $member['m_activation_key']);
		$this->sci->assign('member' , $member);

		$messagebody = $this->sci->fetch('admin/member/email_activation.htm');
		$this->email->message($messagebody);
		$ok = $this->email->send();
		if($ok) {
			$this->load->model('mod_member');
			$this->mod_member->activation_email_sent($m_id);
			$this->session->set_confirm(1);
		} else {
			$this->session->set_confirm(0, $this->email->print_debugger());
		}
		redirect($this->mod_url."view/$m_id");
	}

	function search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$m_status = $this->input->post('m_status');
		$filter = $this->input->post('filter');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$m_status/$filter/$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}

	function disable($m_id=0) {
		$this->db->where('m_id' , $m_id);
		$this->db->set('m_status' , 'Deleted');
		$this->db->set('m_deletion' , date('Y-m-d H:i:s') );
		$this->db->update('member');
		redirect($this->session->get_bread('list'));
	}

	function enable($m_id=0) {
		$this->db->where('m_id' , $m_id);
		$this->db->set('m_status' , 'Active');
		$this->db->update('member');
		redirect($this->session->get_bread('list'));
	}



}
