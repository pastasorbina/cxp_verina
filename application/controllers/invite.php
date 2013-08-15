<?php

class Invite extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->session->validate_member(TRUE);
		$this->sci->assign('userinfo' , $this->userinfo);

		$this->load->library('form_validation');
		$this->load->model('mod_member'); 
	}

	function index() {
		$this->session->set_flashdata('invitee_email', $this->input->post('invitee_email') );
		redirect(site_url()."invite/form");
	}
	
	function fb_share($m_id=0) {
		//get member
		$this->db->where('m_id' , $m_id);
		$res = $this->db->get('member');
		$member = $res->row_array();
		$this->sci->assign('member' , $member);
		
		//get config
		//print_r($this->site_config);
		
		$description_html = $this->sci->fetch('invite/fb_share.htm');
		$this->sci->assign('description_html' , $description_html);
	}
	
	function form() {
		//get invitation default message
		$this->db->where('c_code' , 'invitation-default-message');
		$res = $this->db->get('content');
		$invitation_default_message = $res->row_array();
		$invitation_default_message['c_content_full'] = trim(strip_tags($invitation_default_message['c_content_full']));
		$this->sci->assign('invitation_default_message' , $invitation_default_message);

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = 'Send Invitation';

		if(!$this->session->flashdata('invitee_email')) {
			$invitee_email = $this->input->post('invitee_email');
		} else {
			$invitee_email = $this->session->flashdata('invitee_email');
		}
		$this->sci->assign('invitee_email' , $invitee_email);

		$key = $this->userinfo['m_registration_key'];

		$this->sci->assign('key' , $key); 
		
		$description_html = $this->sci->fetch('invite/fb_share.htm');
		$this->sci->assign('description_html' , $description_html);

		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->sci->da('index.htm');
	}

	function load_sent_invitation_list(){
		$this->db->where('invt_sender_id' , $this->userinfo['m_id']);
		$this->db->where('invt_status' , 'Active');
		$this->db->order_by('invt_send_date' , 'DESC');
		$res = $this->db->get('invitation');
		$sent_invitation = $res->result_array();

		//foreach invitation, check if that email is already registered
		foreach($sent_invitation as $k=>$tmp) {
			$registered = $this->mod_member->check_email_registered($tmp['invt_receiver_email']);
			if($registered) {
				$sent_invitation[$k]['is_registered'] = "Yes";
			} else {
				$sent_invitation[$k]['is_registered'] = "No";
			}
		}
		$this->sci->assign('sent_invitation' , $sent_invitation);
		$this->sci->d('sent_invitation_list.htm');
	}

	function send_invitation(){
		$ok = false;
		$this->form_validation->set_rules('invitee_email', 'Email', 'trim|required|valid_emails|xss_clean');
		$this->form_validation->set_rules('message', 'Message', 'trim|xss_clean');

		$key = $this->userinfo['m_registration_key'];

		if($this->form_validation->run() == FALSE) {
			$ret['status'] = 'error';
			$ret['msg'] = validation_errors();
			echo json_encode($ret);
		} else {
			$invitee_email = $this->input->post('invitee_email');
			$invitee_email = str_replace(' ', '', $invitee_email);
			$emails = explode(',', $invitee_email);
			if(sizeof($emails) > 5) {
				$ret['status'] = 'error';
				$ret['msg'] = 'cannot send to more than 5 emails';
				echo json_encode($ret);
				return false;
			}

			//print_r($emails);

			$message = $this->input->post('message');
			$fullname = $this->userinfo['m_firstname'].' '.$this->userinfo['m_lastname'];

			$sendok = false;

			foreach($emails as $k=>$email) {
				$email = trim($email);
				if($email != '') {
					//get invitation
					$this->db->where('invt_sender_id' , $this->userinfo['m_id']);
					$this->db->where('invt_receiver_email' , $email);
					$res = $this->db->get('invitation');
					$invitation = $res->row_array();

					//check if email is registered AND not his own mail
					if( !$this->mod_member->check_email_registered($email) AND ($this->userinfo['m_login']!=$email) ) {
						//insert/update invitation
						$this->db->set('invt_key' , $key);
						$this->db->set('invt_sender_id' , $this->userinfo['m_id']);
						$this->db->set('invt_receiver_email' , $email);
						$this->db->set('invt_message' , $message);
						$this->db->set('invt_send_count' , 0);
						$this->db->set('invt_send_date' , date('Y-m-d H:i:s'));
						if(!$invitation){
							$this->db->set('invt_entry' , date('Y-m-d H:i:s'));
							$ok = $this->db->insert('invitation');
							$invt_id = $this->db->insert_id();
						}else {
							$invt_id = $invitation['invt_id'];
						}
						//send email
						$sendok = $this->send_mail($invt_id);
					}
				}
			}

			if($sendok) {
				$ret['status'] = 'ok';
				$ret['msg'] = 'Invitation has been sent !';
			} else {
				$ret['status'] = 'error';
				$ret['msg'] = 'sending email invitation failed';
			}
			echo json_encode($ret);
		}

	}

	function send_mail($invt_id){

		//get invitation
		$this->db->join('member m' , 'm.m_id = invitation.invt_sender_id' , 'left');
		$this->db->where('invt_id' , $invt_id);
		$res = $this->db->get('invitation');
		$invitation = $res->row_array();

		if(!$invitation) { return false; }
		$fullname = $invitation['m_firstname']." ".$invitation['m_lastname'];
		$message = $invitation['invt_message'];
		$email = $invitation['invt_receiver_email'];
		$key = $invitation['invt_key'];

		$sendok = FALSE;
		if( !$this->mod_member->check_email_registered($email) AND ($this->userinfo['m_login']!=$email) ) {
			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'Gudang Brands' );
			$this->email->to($email);
			$this->email->subject( $fullname.' would like to invite you to join gudangBrands' );

			$this->sci->assign('invt_id' , $invt_id);
			$this->sci->assign('message' , $message );
			$this->sci->assign('fullname' , $fullname );
			$this->sci->assign('key' , $key.'/'.safe_base64_encode($email) );
			$messagebody = $this->sci->fetch('invite/email_invitation.htm');

			$this->email->message($messagebody);
			$this->email->set_alt_message('This is the alternative message');

			$this->db->where('invt_id' , $invitation['invt_id'] );
			$this->db->set('invt_send_count' , $invitation['invt_send_count']+1 );
			$ok = $this->db->update('invitation');

			$sendok = $this->email->send();
		}
		return $sendok;
	}

	function resend_mail($invt_id) {

		$sendok = $this->send_mail($invt_id);
		if($sendok) {
			$ret['status'] = 'ok';
			$ret['msg'] = 'Invitation has been sent !';
		} else {
			$ret['status'] = 'error';
			$ret['msg'] = 'sending email invitation failed';
			//$ret['msg'] .= $this->email->print_debugger();
		}
		echo json_encode($ret);
	}

	function _check_is_registered($email) {
		$this->db->where('m_login' , $email);
		$this->db->where('m_status' , 'Active');
		return $this->db->count_all_results('member');
	}

	function _generate_key($id) {
		$salt = $this->config->item('salt');
		$key = md5($salt.$id);
		return $key;
	}

	function view_email_template() {
		$message = makeLipsum(2);
		$fullname = '(name here)';
		$key = 'key';
		$this->sci->assign('message' , $message);
		$this->sci->assign('fullname' , $fullname);
		$this->sci->assign('key' , $key);
		$this->sci->d('email_invitation.htm');
	}

	function accept_invitation($email='', $invitation_key='') {
		$email = safe_base64_decode($email);
		print $email;
	}

	function mark_as_read($invt_id=0) {
		$this->db->where('invt_id' , $invt_id);
		$this->db->set('invt_read_status' , 'Read');
		$ok = $this->db->update('invitation');
	}








}
