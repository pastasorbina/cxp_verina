<?php
class Newsletter_recipient extends MY_Controller {

	var $mod_title = 'Newsletter recipient';

	var $table_name = 'newsletter_recipient';
	var $id_field = 'nlrec_id';
	var $status_field = 'nlrec_status';
	var $entry_field = 'nlrec_entry';
	var $stamp_field = 'nlrec_stamp';
	var $deletion_field = 'nlrec_deletion';
	var $order_field = 'nlrec_entry';
	var $order_dir = 'DESC';
	var $label_field = 'nlrec_email';

	var $author_field = 'nlrec_author';
	var $editor_field = 'nlrec_editor';

	var $search_in = array('nlrec_email');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('NEWSLETTER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		//get mailchimp api
		$site_config = $this->config->item('site_config');
		$this->mailchimp_api_key = $site_config['mailchimp_api_key'];
		$this->mailchimp_list_id = $site_config['mailchimp_list_id'];
		$config = array (
                  'apikey' => $this->mailchimp_api_key,
                  'secure'  => false
               );
		$this->load->library('mailchimp', $config);

		$this->make_topbar();
	}

	function make_topbar() {
		$topbar = $this->sci->fetch('admin/newsletter_recipient/topbar.htm');
		$this->sci->assign('topbar' , $topbar);
	}

	function enum_setting($maindata=array()) {
		return $maindata;
	}

	function join_setting() {
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function validation_setting() {
		$this->form_validation->set_rules('nlrec_email', 'Email', 'callback_email_check|trim|required|valid_email|xss_clean');
	}

	function database_setter() {
		$this->db->set('nlrec_email' , $this->input->post('nlrec_email') );
		$this->db->set('nlrec_submitted_by' , 0);
	}


	function pre_add_edit() {
	}

	function pre_add() {
	}

	function pre_edit($id=0) {
	}

	function mc_member_list($id=0){
		if($id==0) { $id = $this->mailchimp_list_id; }
		$this->session->set_bread('list');
		$this->sci->assign('id' , $id);
		//$list = listMembers($id, $status='subscribed', $since=NULL, $start=0, $limit=100)
		$list = $this->mailchimp->listMembers($id, 'subscribed', NULL, 0, 100);
		if($list) {
			$maindata = $list['data'];
			$this->sci->assign('maindata' , $maindata );
			$this->sci->assign('total' , $list['total']);
		}
		$this->sci->da('mailchimp_member_list.htm');
	}

	function mc_unsubscribe($id=0, $email_encoded='') {
		if($id==0) { $id = $this->mailchimp_list_id; }
		$email = safe_base64_decode($email_encoded);
		 //function listUnsubscribe($id, $email_address, $delete_member=false, $send_goodbye=true, $send_notify=true) {
		$ok = $this->mailchimp->listUnsubscribe($id, $email, TRUE, FALSE, FALSE);
		if($ok == FALSE){
			$this->session->set_confirm(0);
		} else {
			$this->session->set_confirm(1);
		}
		redirect( $this->session->get_bread('list') );
	}

	function mc_resubscribe_all_member() {
		//get all member
		$this->db->where('m_status' , 'Active');
		$res = $this->db->get('member');
		$members = $res->result_array();
		foreach($members as $k=>$tmp) {
			$batch[$k]['EMAIL'] = $tmp['m_email'];
			$batch[$k]['EMAIL_TYPE'] = 'html';
		}
		 //function listBatchSubscribe($id, $batch, $double_optin=true, $update_existing=false, $replace_interests=true) {
		 $this->mailchimp->listBatchSubscribe($this->mailchimp_list_id, $batch, FALSE, TRUE, TRUE);
		 redirect( $this->session->get_bread('list') );
	}


	//callback if email already registered
	public function email_check($str) {
		$this->db->where('nlrec_email' , $str);
		$this->db->where('nlrec_status' , 'Active');
		$res = $this->db->get('newsletter_recipient');
		$recipient = $res->row_array();
		if ($recipient) {
			$this->form_validation->set_message('email_check', $str.' is already registered');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function subscribe($nlrec_id=0) {
		$this->db->where('nlrec_id' , $nlrec_id);
		$res = $this->db->get('newsletter_recipient');
		$recipient = $res->row_array();

		$recipient_email = $recipient['nlrec_email'];

		//listSubscribe(string apikey, string id, string email_address, array merge_vars, string email_type, bool double_optin, bool update_existing, bool replace_interests, bool send_welcome)
		$ok = $this->mailchimp->listSubscribe($this->mailchimp_list_id, $recipient_email, array(), 'html', FALSE, TRUE, TRUE, FALSE);

		if($ok == FALSE){
			$this->session->set_confirm(0);
		} else {
			$this->session->set_confirm(1);
		}
		redirect( $this->session->get_bread('list') );
	}




}
