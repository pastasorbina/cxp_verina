<?php

class Facebook extends My_Controller {

	function __construct() {
		parent::__construct();
		$this->client_id = "400981713258135";
		$this->secret_id = "182c11eda40fdeaee60923490effb766";
		$this->state = 'FB_STATE_' . $this->session->userdata('session_id');
		$this->scope = "user_birthday,user_hometown,user_status,email,publish_actions";
		$this->redir_uri = site_url() . "facebook/code_receive";
		$this->oauth_url = "https://www.facebook.com/dialog/oauth?client_id={$this->client_id}&redirect_uri={$this->redir_uri}&scope={$this->scope}&state={$this->state}";
	}

	function index() {
		$this->sci->da('index.htm');
	}

	function login() {
		header("Location: {$this->oauth_url}");
	}

	function code_receive() {
		$state = $this->input->get('state');
		$code = $this->input->get('code');
		$error_reason = $this->input->get('error_reason');
		$error = $this->input->get('error');
		$error_description = $this->input->get('error_description');

		if ($code != '') {
			if ($state != $this->state) {
				$this->sci->assign('error_description' , "Error, Session is already expired.");
				$this->sci->da('code_receive_error.htm');
			}

			// Code OK
			// Get the user access token
			$url = "https://graph.facebook.com/oauth/access_token?client_id={$this->client_id}&redirect_uri={$this->redir_uri}&client_secret={$this->secret_id}&code={$code}";
			$this->load->library('rpc');
			$ret = $this->rpc->HTTPGet($url);

			preg_match("/^access_token=(.+)&expires=(\d+)$/" , $ret , $matches);
			list($buang , $access_token , $expires) = $matches;

			// Get user information
			$url = "https://graph.facebook.com/me?access_token={$access_token}";
			$fb_raw = $this->rpc->HTTPGet($url);
			$fb = json_decode($fb_raw);

			// Find this user on our user database
			$this->db->where('fb_id' , $fb->id);
			$this->db->or_where('m_login', $fb->email);
			$this->db->where('m_status' , 'Active');
			$res = $this->db->get('member');

			if ($res->num_rows() > 0) {
				$user = $res->row();
				// User found, update data
				$this->db->set('fb_expires' , "NOW() + INTERVAL {$expires} SECOND" , false);
				$this->db->set('fb_access_token' , $access_token);
				$this->db->where('m_id' , $user->m_id);
				$this->db->update('member');

				$m_id = $user->m_id;
			}
			else {
				// Create new information about this user
				$this->db->set('m_firstname' , $fb->first_name);
				$this->db->set('m_lastname' , $fb->last_name);
				$this->db->set('m_email' , $fb->email);
				$this->db->set('m_login' , $fb->email);
				$this->db->set('m_sex' , $fb->gender);
				$this->db->set('fb_id' , $fb->id);
				$this->db->set('fb_expires' , "NOW() + INTERVAL {$expires} SECOND" , false);
				$this->db->set('fb_access_token' , $access_token);
				$this->db->set('fb_raw_json' , $fb_raw);
				$this->db->set('m_entry' , 'NOW()' , false);
				$this->db->set('m_registration_key' , generate_registration_key($fb->email) );
				$this->db->set('m_activation_key' , generate_activation_key($fb->email) );  
				$this->db->set('m_is_active' , 'Yes');
				$this->db->insert('member');

				$m_id = $this->db->insert_id();
			}

			// Login this user
			$this->session->set_userdata('m_id' , $m_id);

			//update last login
			$this->db->set('m_last_login' , 'NOW()' , FALSE);
			$this->db->where('m_id' , $m_id );
			$this->db->update('member');

			// Redirect to home
			redirect('');
		}
		else {
			$this->sci->assign('error_description' , $error_description);
			$this->sci->da('code_receive_error.htm');
		}
	}
}

// End of file /home/rickyok/www/gudangbrands.com/application/controllers/facebook_test.php
// Create time : Mon May 07 15:41:05 2012
// Creator : Frederick Lasmana
