<?php

class Newsletter extends MY_Controller {

	var $mod_title = 'Home';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		//$this->session->validate_member();

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

	function test_api() {
		//$lists = $this->mailchimp->listCampaigns();
		//print_r($lists);
	}

	function ajax_subscribe_email(){
		$recipient_email = $this->input->post('recipient_email');

		$this->load->helper('email');
		if(!valid_email($recipient_email)) {
			$ret['status'] = 'error';
			$ret['msg'] = 'Not a valid email !';
			echo json_encode($ret); return FALSE;
		}

		$this->load->library('mailchimp');

		//listSubscribe(string apikey, string id, string email_address, array merge_vars, string email_type, bool double_optin, bool update_existing, bool replace_interests, bool send_welcome)
		$ok = $this->mailchimp->listSubscribe($this->mailchimp_list_id, $recipient_email, array(), 'html', false, false, true, false);

		if($ok == FALSE){
			$ret['status'] = 'error';
			$ret['msg'] = 'Cannot subscribe Email.';
		} else {
			$ret['status'] = 'ok';
			$ret['msg'] = 'Mail has been subscribed, Thank You !';
		}
		echo json_encode($ret);
	}

	//function ajax_send_campaign() {
	//	$campaign_list = $this->mailchimp->campaigns(array( 'title' => 'Newsletter' ), 0, 1);
	//	$campaign = $campaign_list['data'][0];
	//	$ok = $this->mailchimp->campaignSendNow($campaign['id']);
	//
	//	if($ok == FALSE) {
	//		$ret['status'] = 'error';
	//		$ret['msg'] = 'cannot send newsletter !';
	//	} else {
	//		$ret['status'] = 'ok';
	//		$ret['msg'] = 'newsletter sent !';
	//	}
	//
	//	echo json_encode($ret);
	//}

	function _check_recipient_exist($email=''){
		$this->db->where('nlrec_email' , $email);
		$this->db->where('nlrec_status' , 'Active');
		$numof = $this->db->count_all_results('newsletter_recipient');
		return $numof;
	}

}
