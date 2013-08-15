<?php

class Form extends MY_Controller {

	var $mod_title = 'Form';

	function __construct() {
		parent::__construct();
		$this->sci->init('front');
		$this->_init();
		$this->load->helper('captcha');
		$this->session->validate_member(FALSE);
	}

	function view($f_id = 0 , $f_name = '') {

		$this->_load_sidebar();
		$this->load->library('form_validation');

		$this->db->where('f_id' , $f_id);
		$res = $this->db->get('form');
		$form = $res->row_array();
		if(!$form) { redirect(site_url()); }
		$this->sci->assign('form' , $form);

		$this->db->where('f_id' , $f_id);
		$this->db->order_by('f_id' , 'ASC');
		$res = $this->db->get('form_detail');
		$form_detail = $res->result_array();
		foreach ($form_detail as $k => $v) {
			if ($v['fd_options']) {
				$form_detail[$k]['fd_options_ex'] = explode('|' , $v['fd_options']);
			}
		}
		$this->sci->assign('form_detail' , $form_detail);

		// Update f_hit
		$sql = "
			UPDATE form SET f_hit = f_hit + 1 WHERE f_id = ?
		";
		$this->db->query($sql , array($f_id));

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $form['f_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);


		$this->_generate_captcha();
		$this->sci->da('view.htm');
	}



	function submit($f_id = 0) {
		$this->load->library('form_validation');

		$this->db->where('f_id' , $f_id);
		$res = $this->db->get('form');
		$form = $res->row_array();
		if(!$form) { redirect(site_url()); }
		$this->sci->assign('form' , $form);

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $form['f_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->db->where('f_id' , $f_id);
		$this->db->order_by('f_id' , 'ASC');
		$res = $this->db->get('form_detail');
		$form_detail = $res->result_array();
		$this->sci->assign('form_detail' , $form_detail);

		// Define form validation sequence
		foreach ($form_detail as $k => $v) {
			$param = array();
			if ($v['fd_req'] == 'True') {
				$param[] = 'required';
			}
			if (trim($v['fd_regex']) != '') {
				$base64_regex = base64_encode(trim($v['fd_regex']));
				$param[] = "callback__regex[$base64_regex]";
			}
			$param_im = implode('|' , $param);
			$this->form_validation->set_rules($v['fd_id'], $v['fd_key'], $param_im);
		}

		$this->form_validation->set_rules('captcha_answer', 'Captcha', 'trim|alpha_numeric|callback__checkcaptcha|xss_clean|required');

		if ($this->form_validation->run() == FALSE) {
			$this->_generate_captcha();
			$this->sci->da('view.htm');
		}
		else {
			// Build email
			foreach ($form_detail as $k => $v) {
				$value = set_value($v['fd_id']);
				if (is_array($value)) $value = implode(' , ' , $value);
				$form_detail[$k]['value'] = $value;
			}
			$this->sci->assign('form_detail' , $form_detail);
			$result = $this->sci->fetch('submit.htm');

			// Insert into database
			$this->db->
				set('fr_content' , $result)->
				set('fr_entry_time' , 'NOW()' , FALSE)->
				insert('form_result');

			// Send the email
			$this->load->library('email');
			$this->email->from($this->session->get_config('ADMIN_EMAIL'));
			$this->email->to($form->f_destination_emails);
			$this->email->subject('[Form] ' . $form->f_name);
			$this->email->message($result);
			$this->email->send();
		}

	}

	function form_success() {
		$this->sci->da('template');
	}

	function _regex($input , $base64_regex) {
		$regex = base64_decode($base64_regex);
		if (preg_match("/$regex/" , $input)) {
			return true;
		}
		else {
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
