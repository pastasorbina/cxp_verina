<?php
class Newsletter extends MY_Controller {

	var $mod_title = 'Newsletter Campaigns';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'newsletter_campaign';
	var $id_field = 'nlc_id';
	var $status_field = 'nlc_status';
	var $entry_field = 'nlc_entry';
	var $stamnlc_field = 'nlc_stamp';
	var $deletion_field = 'nlc_deletion';
	//var $order_field = 'nlc_scheduled_date';
	var $order_field = 'nlc_entry';
	var $order_dir = 'DESC';

	var $author_field = 'nlc_author';
	var $editor_field = 'nlc_editor';

	var $search_in = array('nlc_name');

	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('NEWSLETTER_MANAGE'), 'admin');
		$this->sci->assign('use_ajax' , FALSE);

		$this->load->model('mod_newsletter');

		$this->site_config = $this->config->item('site_config');
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
		$topbar = $this->sci->fetch('admin/newsletter/topbar.htm');
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
		$this->form_validation->set_rules('nlc_name', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('nlc_scheduled_date', 'Scheduled Date', 'trim|xss_clean');
		$this->form_validation->set_rules('nlc_content', 'Content', '');
		$this->form_validation->set_rules('nlc_header', 'Header', '');
		$this->form_validation->set_rules('nlc_footer', 'Footer', '');
	}

	function database_setter() {
		$this->db->set('nlc_name' ,  $this->input->post('nlc_name'));
		$this->db->set('nlc_content' ,  $this->input->post('nlc_content'));
		$this->db->set('nlc_scheduled_date' ,  $this->input->post('nlc_scheduled_date'));
		$this->db->set('nlc_header' ,  $this->input->post('nlc_header'));
		$this->db->set('nlc_footer' ,  $this->input->post('nlc_footer'));
	}


	function pre_add_edit() { $this->config->set_item('global_xss_filtering', FALSE); }
	function pre_add() {
		$default_header = $this->site_config['newsletter_default_header'];
		$data['nlc_header'] = $default_header;
		
		$default_footer = $this->site_config['newsletter_default_footer'];
		$data['nlc_footer'] = $default_footer;
		$this->sci->assign('data' , $data);
	}
	function pre_edit($id=0) { }


	function make_email($nlc_id=0) {
		$this->db->where('nlc_id' , $nlc_id);
		$this->db->where('nlc_status' , 'Active');
		$res = $this->db->get('newsletter_campaign');
		$nlc = $res->row_array();
		
		$default_header = $this->site_config['newsletter_default_header'];
		$default_footer = $this->site_config['newsletter_default_footer'];
		
		if(trim($nlc['nlc_header']) == '') {
			$header = $default_header;
		} else {
			$header = $nlc['nlc_header'];
		}
		
		if(trim($nlc['nlc_footer']) == '') {
			$footer = $default_footer;
		} else {
			$footer = $nlc['nlc_footer'];
		}
		$this->sci->assign('newsletter_header' , $header);
		$this->sci->assign('newsletter_footer' , $footer);
		$this->sci->assign('content' , $nlc['nlc_content']);
		$html = $this->sci->fetch('admin/newsletter_campaign/mail_template.htm');
		return $html;
	}

	function view_template($nlc_id=0) {
		$html = $this->make_email($nlc_id);
		echo $html;
	}

	function publish($nlc_id=0) {
		$ok = false;
		$ret['status'] = 'error';

		$this->db->where('nlc_id' , $nlc_id);
		$this->db->where('nlc_status' , 'Active');
		$res = $this->db->get('newsletter_campaign');
		$nlc = $res->row_array();

		//setup options
		$opts['list_id'] = $this->site_config['mailchimp_list_id'];
		$opts['title'] = $nlc['nlc_name'];
		$opts['subject'] = '[Gudang Brands] '.$nlc['nlc_name'];
		$opts['from_email'] = 'info@gudangbrands.com';
		$opts['from_name'] = 'Gudang Brands';
		$opts['to_name'] = '';
		$opts['auto_footer'] = TRUE; //optional
		$opts['generate_text'] = TRUE; //optional

		//setup content
		//$this->sci->assign('content' , $nlc['nlc_content']);
		//$content['html'] = $this->sci->fetch('admin/newsletter_campaign/mail_template.htm');
		$html = $this->make_email($nlc_id);
		$content['html'] = $html;

		//publish to mailchimp
		//campaignCreate(string apikey, string type, array options, array content, array segment_opts, array type_opts)
		$mailchimp_cid = $this->mailchimp->campaignCreate('regular', $opts, $content, array() , array() );

		if(!$mailchimp_cid) {
			if(!$ok) {
				$ret['msg'] = 'Failed to publish to mailchimp';
				echo json_encode($ret); return false;
			}
		} else {
			//get gmt time from scheduled date
			$scheduled_date = $nlc['nlc_scheduled_date'];
			$scheduled_date_unix = mysql_to_unix($scheduled_date);
			$scheduled_date_gmt = gmdate('Y-m-d H:i:s', $scheduled_date_unix);

			//set mailchimp schedule
			$ok = $this->mailchimp->campaignSchedule($mailchimp_cid, $scheduled_date_gmt);
			if(!$ok) {
				$ret['msg'] = 'Cannot change Campaign Schedule';
				echo json_encode($ret); return false;
			}

			$this->db->where('nlc_id' , $nlc_id);
			$this->db->set('nlc_publish_date' , date('Y-m-d H:i:s') );
			$this->db->set('nlc_publish_status' , 'Published');
			$this->db->set('nlc_mailchimp_cid' , $mailchimp_cid);
			$ok = $this->db->update('newsletter_campaign');

			if(!$ok) {
				$ret['msg'] = 'Cannot Update Database';
				echo json_encode($ret); return false;
			}
		}

		if($ok) {
			$ret['status'] = 'ok';
			$ret['msg'] = 'Published Successfully';
			echo json_encode($ret);
		}
	}


	function mc_list(){
		$list = $this->mod_newsletter->get_list();
		$this->sci->assign('maindata' , $list );
		$this->sci->da('mc_list.htm');
	}

	function mc_member_list($offset=0, $pagelimit=20){
		$this->session->set_bread('list');
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('pagelimit' , $pagelimit);



		$list = $this->mod_newsletter->get_list();
		$total = $list['stats']['member_count'];
		$this->sci->assign('total' , $total);

		$pagenum = $offset / $pagelimit;

		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."mc_member_list/";
		$config['suffix'] = "/$pagelimit" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);
		$this->sci->assign('paging', $this->pagination->create_links() );

		$maindata = $this->mod_newsletter->list_member($pagenum, $pagelimit);

		if($maindata) {
			$this->sci->assign('maindata' , $maindata['data'] );
			$this->sci->assign('displayed_total' , sizeof($maindata['data']) );
		}
		$this->sci->da('mc_member_list.htm');
	}

	function mc_unsubscribe($id=0, $email_encoded='') {
		if($id==0) { $id = $this->mailchimp_list_id; }
		$email = safe_base64_decode($email_encoded);
		$ok = $this->mod_newsletter->unsubscribe($email);
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
		//$this->mailchimp->listBatchSubscribe($this->mailchimp_list_id, $batch, FALSE, TRUE, TRUE);
		$ok = $this->mod_newsletter->batch_subscribe($batch);
		if($ok == FALSE){
			$this->session->set_confirm(0);
		} else {
			$this->session->set_confirm(1);
		}
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

	function duplicate($nlc_id=0) {
		$this->db->where('nlc_id' , $nlc_id);
		$res = $this->db->get('newsletter_campaign');
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		
		$this->load->library('form_validation');
		$this->validation_setting('add');
		if( $this->form_validation->run() == FALSE ) {
			$this->sci->da('duplicate.htm');
		} else {
			$this->database_setter('add');
			$this->db->set('nlc_entry' , 'NOW()', FALSE);
			$ok = $this->db->insert('newsletter_campaign');
			if($ok == FALSE){
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect( $this->session->get_bread('list') );	
		}
	}


}
