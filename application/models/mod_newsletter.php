<?php

class Mod_newsletter extends MY_Model {


	function __construct(){
		parent::__construct();
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

	function get_list() {
		$config = array();
		$config['list_id'] = $this->mailchimp_list_id;
		$list = $this->mailchimp->lists($config, 0, 1);
		$data = array();
		if($list) {
			$data = $list['data'][0];
		}
		return $data;
	}

	function subscribe($recipient_email='') {
		$ok = $this->mailchimp->listSubscribe($this->mailchimp_list_id, $recipient_email, array(), 'html', FALSE, TRUE, TRUE, FALSE);
		return $ok;
	}

	function batch_subscribe($batch=array() ) {
		$ok = $this->mailchimp->listBatchSubscribe($this->mailchimp_list_id, $batch, FALSE, TRUE, TRUE);
		return $ok;
	}

	function unsubscribe($recipient_email=''){
		 //function listUnsubscribe($id, $email_address, $delete_member=false, $send_goodbye=true, $send_notify=true) {
		$ok = $this->mailchimp->listUnsubscribe($this->mailchimp_list_id, $recipient_email, TRUE, FALSE, FALSE);
		return $ok;
	}

	function list_member($offset=0, $limit=100, $status='', $where=NULL ) {
		if($status == ''){ $status = 'subscribed'; }
		//$list = listMembers($id, $status='subscribed', $since=NULL, $start=0, $limit=100)
		$list = $this->mailchimp->listMembers($this->mailchimp_list_id, $status, $where, $offset, $limit);
		return $list;
	}
}

?>
