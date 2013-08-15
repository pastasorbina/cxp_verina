<?php

class Invite extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();

		$this->session->validate_member();
		$this->sci->assign('userinfo' , $this->userinfo);

		$this->load->library('form_validation');
	}

	function index() {
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = 'Send Invitation';



		$this->sci->assign('breadcrumb' , $breadcrumb);
		$this->sci->da('index.htm');
	}

	function load_sent_invitation_list(){
		$this->db->where('invt_sender_id' , $this->userinfo['m_id']);
		$this->db->where('invt_status' , 'Active');
		$this->db->order_by('invt_send_date' , 'DESC');
		$res = $this->db->get('invitation');
		$sent_invitation = $res->result_array();
		$this->sci->assign('sent_invitation' , $sent_invitation);
		$this->sci->d('sent_invitation_list.htm');
	}

	function send_invitation(){
		$ok = false;
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');
		$this->form_validation->set_rules('message', 'Message', 'trim|xss_clean');

		if($this->form_validation->run() == FALSE) {
			$ret['status'] = 'error';
			$ret['msg'] = validation_errors();
			echo json_encode($ret);
		} else {
			$email = $this->input->post('email');
			$message = $this->input->post('message');
			$fullname = $this->userinfo['m_firstname'].' '.$this->userinfo['m_lastname'];

			if($this->_check_is_registered($email) != 0) {
				//$ret['status'] = 'error';
				//$ret['msg'] = 'email already registered !';
				//echo json_encode($ret);
				//return false;
			}

			$this->load->library('email');
			$config['mailtype'] = 'html';
			$config['charset'] = 'iso-8859-1';
			$this->email->initialize($config);
			$this->email->from('info@gudangbrands.com', 'info' );
			$this->email->to($email);
			$this->email->subject( $fullname.' would like to invite you to join gudangBrands' );

			$key = $this->_generate_key($email);
			//$link = 'http://beta.gudangbrands.com/invite/acc/'.$key;

			$this->sci->assign('message' , $message);
			$this->sci->assign('fullname' , $fullname);
			$this->sci->assign('key' , $key);
			$messagebody = $this->sci->fetch('mail_templates/invitation.htm');
			$this->email->message($messagebody);

			$ok = $this->email->send();

			$ret['status'] = 'error';
			$ret['msg'] = 'cannot deliver your email, please try again';
			$ret['msg'] .= $this->email->print_debugger();

			if($ok) {

				$this->db->where('invt_sender_id' , $this->userinfo['m_id']);
				$this->db->where('invt_receiver_email' , $email);
				$res = $this->db->get('invitation');
				$invitation = $res->row_array();


				$this->db->set('invt_key' , $key);
				$this->db->set('invt_sender_id' , $this->userinfo['m_id']);
				$this->db->set('invt_receiver_email' , $email);
				$this->db->set('invt_message' , $message);
				$this->db->set('invt_send_date' , date('Y-m-d H:i:s'));
				if(!$invitation){
					$this->db->set('invt_entry' , date('Y-m-d H:i:s'));
					$ok = $this->db->insert('invitation');
				}else {
					$this->db->where('invt_id' , $invitation['invt_id'] );
					$this->db->set('invt_send_count' , $invitation['invt_send_count']+1 );
					$ok = $this->db->update('invitation');
				}

				$ret['status'] = 'ok';
				$ret['msg'] = 'Invitation has been sent to '.$email.' !';
			} else {
				$ret['status'] = 'error';
				$ret['msg'] = 'cannot deliver your email, please try again';
				$ret['msg'] .= $this->email->print_debugger();
			}
			echo json_encode($ret);
		}

	}

	function _check_is_registered($email) {
		$this->db->where('m_login' , $email);
		$this->db->where('m_status' , 'Active');
		return $this->db->count_all_results('member');
	}

	function _generate_key($email) {
		$salt = $this->config->item('salt');
		$key = md5($salt.$email);
		return $key;
	}








}
