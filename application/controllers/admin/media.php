<?php
class Media extends MY_Controller {

	var $mod_title = 'Content Management';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->load->model('mod_media');
		$this->load->model('mod_media_category');

		$this->image_directory = "userfiles/media/";
		$this->thumb_directory = "userfiles/media/thumb/";
		$this->thumb_width = 200;
		$this->thumb_height = 200;
		$this->maintain_ratio = TRUE;
	}

	function index() {
		$this->view_list();
	}

	function ajax_upload( $mc_slug = '' ) {

		$this->output->enable_profiler(FALSE);
		//get category by slug
		$where = array('mc_slug' => $mc_slug);
		$media_category = $this->mod_media_category->get('*', $where);
		if($media_category) {
			$mc_id = $media_category['mc_id'];
		} else {
			$this->db->set('mc_name' , $mc_slug);
			$this->db->set('mc_slug' , $mc_slug);
			$this->db->set('b_id' , $this->branch_id );
			$this->db->set('mc_entry' , date('Y-m-d H:i:s') );
			$this->db->insert('media_category');
			$mc_id = $this->db->insert_id();
		}
		$this->sci->assign('mc_id' , $mc_id);
		$this->sci->assign('mc_slug' , $mc_slug);
		$this->load->library('form_validation');
		if($this->form_validation->run() == FALSE) {
			$this->sci->d('ajax_upload.htm');
		} else {

		}
	}

	function ajax_do_upload() {
		$this->output->enable_profiler(FALSE);

		if($_FILES['m_file']) {
			$filename = $this->_upload_image('m_file');
		}

		if(!$filename) {
			$data['status'] = 'error';
			$data['msg'] = 'no file uploaded !';
		} else {
			$this->db->set('m_uploader_id' , $this->userinfo['u_id']);
			$this->db->set('b_id' , $this->branch_id );
			$this->db->set('m_file' , $filename);
			$this->db->set('mc_id' , $this->input->post('mc_id'));
			$this->db->set('m_title' , $this->input->post('m_title'));
			$this->db->set('m_desc' , $this->input->post('m_desc'));
			$this->db->set('m_entry' , date('Y-m-d H:i:s') );

			if(!$this->db->insert('media')){
				$data['status'] = 'error';
				$data['msg'] = 'cannot insert to database !';
			} else {
				$insert_id = $this->db->insert_id();
				$data['status'] = 'ok';
				$data['m_id'] = $insert_id;
				$data['filename'] = $filename;
				$data['file'] = $this->image_directory.$filename;
				$data['thumb'] = $this->thumb_directory.$filename;
			}
		}
		print json_encode($data);
	}


	 function ajax_view_list($mc_slug='', $pagelimit=10, $offset=0) {
		$this->sci->assign('mc_slug' , $mc_slug);

		$this->db->start_cache();
		$this->db->join('media_category' , 'media_category.mc_id = media.mc_id' , 'left');
		$this->db->where('mc_slug' , $mc_slug);
		$this->db->where('media.b_id' , $this->branch_id );
		$this->db->where('m_status' , 'Active');
		$this->db->order_by('m_entry' , 'DESC');
		$this->db->stop_cache();

		$total = $this->db->count_all_results('media');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url.'ajax_view_list/'. $mc_slug .'/'. $pagelimit .'/';
		$config['suffix'] = "" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
//      $config['target_div'] = '#list_box';
		$this->pagination->initialize($config);
		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('media');
		$maindata = $res->result_array();
		$this->db->flush_cache();
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
        $this->sci->assign('paging_js', $this->pagination->create_js() );

		$this->sci->d('ajax_list.htm');
	}

	function ajax_remove($m_id=0) {
		if(!$this->mod_media->delete($m_id)) {
			$data['status'] = 'error';
			$data['msg'] = 'cannot remove media';
		} else {
			$data['status'] = 'ok';
		}
		print json_encode($data);
	}


	//function view_list($pagelimit=10, $offset=0) {
	//	$this->session->set_bread('list');
	//
	//	$where = array();
	//	$where['c_status'] = 'Active';
	//	$order = array();
	//	$order['c_entry'] = 'DESC';
	//
	//	$total = $this->mod_content->count($where);
	//	$this->load->library('pagination');
	//	$config['base_url'] = $this->mod_url.'view_list/'. $pagelimit .'/';
	//	$config['suffix'] = "" ;
	//	$config['total_rows'] = $total;
	//	$config['per_page'] = $pagelimit;
	//	$config['uri_segment'] = 5;
	//	$this->pagination->initialize($config);
	//	$maindata = $this->mod_content->get_list('*', $where, $pagelimit, $offset, $order);
	//	$this->sci->assign('maindata' , $maindata);
	//	$this->sci->assign('paging', $this->pagination->create_links() );
	//	$this->sci->da('list.htm');
	//}
	//
	//function add() {
	//	$this->load->library('form_validation');
	//	$this->_set_rules();
	//	$this->form_validation->set_rules('u_pass', 'Password', 'trim|matches[u_pass_repeat]|required|xss_clean');
	//	$this->form_validation->set_rules('u_pass_repeat', 'Password', 'trim|required|xss_clean');
	//	$this->form_validation->set_rules('u_login', 'Username', 'trim|required|xss_clean');
	//	if($this->form_validation->run() == FALSE ) {
	//		$this->sci->da('add.htm');
	//	}else{
	//		$data['u_login'] = $this->input->post('u_login');
	//		$data['u_name'] = $this->input->post('u_name');
	//		$data['u_email'] = $this->input->post('u_email');
	//
	//		$salt = $this->config->item('salt');
	//		if($this->input->post('u_pass')) {
	//			$data['u_pass'] = md5($salt.$this->input->post('u_pass'));
	//		}
	//
	//		if( !$this->mod_user->insert($data) ) {
	//			$this->session->set_confirm(0);
	//		} else {
	//			$this->session->set_confirm(1);
	//		}
	//		redirect($this->mod_url.'view_list');
	//	}
	//}
	//
	//function _set_rules() {
	//
	//}


}
