<?php
class Newsletter_campaign extends MY_Controller {

	var $mod_title = 'Newsletter Campaigns';
	var $available_position = array();
	var $default_option = array();

	var $table_name = 'newsletter_campaign';
	var $id_field = 'nlc_id';
	var $status_field = 'nlc_status';
	var $entry_field = 'nlc_entry';
	var $stamnlc_field = 'nlc_stamp';
	var $deletion_field = 'nlc_deletion';
	var $order_field = 'nlc_scheduled_date';
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
	}

	function database_setter() {
		$this->db->set('nlc_name' ,  $this->input->post('nlc_name'));
		$this->db->set('nlc_content' ,  $this->input->post('nlc_content'));
		$this->db->set('nlc_scheduled_date' ,  $this->input->post('nlc_scheduled_date'));
	}


	function pre_add_edit() { $this->config->set_item('global_xss_filtering', FALSE); }
	function pre_add() { }
	function pre_edit($id=0) { }

	//function view_template($nlc_id=0) {
	//	$this->db->where('nlc_id' , $nlc_id);
	//	$this->db->where('nlc_status' , 'Active');
	//	$res = $this->db->get('newsletter_campaign');
	//	$nlc = $res->row_array();
	//
	//	$this->sci->assign('content' , $nlc['nlc_content']);
	//	$this->sci->d('admin/newsletter_campaign/mail_template.htm', TRUE);
	//}

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
		print 'asd';
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
		$html = $this->make_email($nlc_id);
		//$this->sci->assign('content' , $nlc['nlc_content']);
		//$content['html'] = $this->sci->fetch('admin/newsletter_campaign/mail_template.htm'); 
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


	function mailchimp_list(){
		//function lists($filters=array (), $start=0, $limit=25)
		$list = $this->mailchimp->lists(array(), 0, 25);
		if($list) {
			$this->sci->assign('maindata' , $list['data']);
			$this->sci->assign('total' , $list['total']);
		}
		//print_r($list);
		$this->sci->da('mailchimp_list.htm');
	}




}
