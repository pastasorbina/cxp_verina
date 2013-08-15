<?php

class Inquiry extends MY_Controller {

	var $mod_title = 'Form';

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
		$this->load->helper('captcha');
	}

	function view($inqt_code='') {

		$this->load->library('form_validation');

		$this->db->where('inqt_code' , $inqt_code);
		$res = $this->db->get('inquiry_type');
		$inquiry_type = $res->row_array();
		if(!$inquiry_type) { show_404(); }
		$this->sci->assign('inquiry_type' , $inquiry_type);


		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $inquiry_type['inqt_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->form_validation->set_rules('captcha_answer', 'Captcha', 'trim|alpha_numeric|callback__checkcaptcha|xss_clean|required');
		$this->form_validation->set_rules('email', 'Email', 'trim|email|xss_clean|required');
		$this->form_validation->set_rules('sender_name', 'Name', 'trim|xss_clean|required');
		$this->form_validation->set_rules('content', 'Inquiry', 'trim|xss_clean|required');
		$this->form_validation->set_rules('subject', 'Subject', 'trim|xss_clean|required');

		if ($this->form_validation->run() == FALSE) {
			$this->_generate_captcha();
			$this->sci->da('view.htm');
		} else {
			$this->db->set('inqt_id' , $inquiry_type['inqt_id'] );
			$this->db->set('inqr_sender_name' , $this->input->post('sender_name') );
			$this->db->set('inqr_email' , $this->input->post('email') );
			$this->db->set('inqr_content' , $this->input->post('content') );
			$this->db->set('inqr_subject' , $this->input->post('subject') );
			$this->db->set('inqr_entry' , 'NOW()', FALSE);
			$this->db->insert('inquiry_result');
			redirect( site_url()."inquiry/success/".$inquiry_type['inqt_code']);
		}
	}

	function success($inqt_code='') {
		$this->db->where('inqt_code' , $inqt_code);
		$res = $this->db->get('inquiry_type');
		$inquiry_type = $res->row_array();
		if(!$inquiry_type) { redirect(site_url()); }
		$this->sci->assign('inquiry_type' , $inquiry_type);

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $inquiry_type['inqt_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('success.htm');
	}


	// Update f_hit
	//$sql = "
	//	UPDATE form SET f_hit = f_hit + 1 WHERE f_id = ?
	//";
	//$this->db->query($sql , array($f_id));


	function form_success() {
		$this->sci->da('template');
	}

	function _regex($input , $base64_regex) {
		$regex = base64_decode($base64_regex);
		if (preg_match("/$regex/" , $input)) {
			return true;
		} else {
			$this->form_validation->set_message('_regex', 'The %s field is invalid format');
			return false;
		}
	}

	function _generate_captcha() {
		$this->load->helper('captcha');
		$word = rand(111111, 999999);
		$vals = array(
			'word' => $word,
			'img_path' => './userfiles/captcha/',
			'img_url' => site_url()."userfiles/captcha/",
			'font_path' => './fonts/calibri.ttf',
			'img_width' => 150,
			'img_height' => 30,
			'expiration' => 7200
			);
		$captcha = create_captcha($vals);
		$this->session->set_userdata('captcha_string', $captcha['word']);
		$this->sci->assign('captcha' , $captcha);
		return $captcha;
	}

	function _checkcaptcha($str){
		$captcha_string = $this->session->userdata('captcha_string');
		if ($str != $captcha_string) {
			$this->form_validation->set_message('_checkcaptcha', 'Captcha Answer not match !');
			return FALSE;
		} else {
			return TRUE;
		}
	}
}

?>
