<?php
class Gallery extends MY_Controller {

	var $mod_title = 'Gallery Management';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->load->model('mod_gallery');
		$this->load->model('mod_gallery_album');

		$this->image_directory = "userfiles/content/";
		$this->thumb_directory = "userfiles/content/thumb/";
		$this->thumb_width = 95;
		$this->thumb_height = 95;

		$this->_load_sidebar();
	}

	function index() {
		$this->view_list();
	}

	function album_view_list( $pagelimit=10, $offset=0) {
		$this->session->set_bread('list');
		$where = array();
		$where['ga_status'] = 'Active';
		$order = array();
		$order['ga_entry'] = 'DESC';
		$total = $this->mod_gallery_album->count($where);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url.'view_list/'. $pagelimit .'/';
		$config['suffix'] = "" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);
		$maindata = $this->mod_gallery_album->get_list('*', $where, $pagelimit, $offset, $order);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('album_list.htm');
	}

	function album_add() {
		$this->load->library('form_validation');
		$this->_album_set_rules();
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('album_add.htm');
		}else{
			$this->image_directory = "userfiles/gallery/album/";
			$this->thumb_directory = "userfiles/gallery/album/thumb/";
			$this->thumb_width = 150;
			$this->thumb_height = 150;
			$filename = $this->_upload_image('ga_image');
			if($filename) {
				$data['ga_image'] = $filename;
			}
			$data['ga_name'] = $this->input->post('ga_name');
			$data['ga_slug'] = 	str_replace(' ','_', xss_clean($this->input->post('ga_name')));
			$data['ga_desc'] = $this->input->post('ga_desc');

			if( !$this->mod_gallery_album->insert($data) ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url."album_view_list");
		}
	}

	function album_edit($ga_id=0) {
		$this->load->library('form_validation');
		$this->_album_set_rules();
		if($this->form_validation->run() == FALSE ) {
			$album = $this->mod_gallery_album->get_by_id($ga_id);
			$this->sci->assign('album' , $album);
			$this->sci->da('album_edit.htm');
		}else{
			$this->image_directory = "userfiles/gallery/album/";
			$this->thumb_directory = "userfiles/gallery/album/thumb/";
			$this->thumb_width = 150;
			$this->thumb_height = 150;

			if($_FILES['name'] = '') {
				$data['ga_image'] = $this->input->post('ga_image_default');
			} else {
				$filename = $this->_upload_image('ga_image');
				if($filename) {
					$data['ga_image'] = $filename;
				}
			}
			$data['ga_name'] = $this->input->post('ga_name');
			$data['ga_slug'] = 	str_replace(' ','_', xss_clean($this->input->post('ga_name')));
			$data['ga_desc'] = $this->input->post('ga_desc');
			if( !$this->mod_gallery_album->update($ga_id, $data) ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url."album_view_list");;
		}
	}


	function _album_set_rules() {
		$this->form_validation->set_rules('ga_name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('ga_desc', 'Desc', 'trim|xss_clean');
		$this->form_validation->set_rules('ga_image_default', 'Default Image', 'trim|xss_clean');
	}



	function view_list( $ga_id = 0, $g_type='Any', $pagelimit=10, $offset=0) {
		$this->session->set_bread('list');
		$this->sci->assign('ga_id' , $ga_id);
		$this->sci->assign('ga_type' , $g_type);

		$where = array();
		$where['ga_status'] = 'Active';
		$all_album = $this->mod_gallery_album->get_list('*');
		$this->sci->assign('all_album' , $all_album);
		$album = $this->mod_gallery_album->get_by_id($ga_id);
		$this->sci->assign('album' , $album);

		$where = array();
		$where['g_status'] = 'Active';
		$where['ga_id'] = $ga_id;
		if($g_type != 'Any') {
			$where['g_type'] = $g_type;
		}
		$order = array();
		$order['g_entry'] = 'DESC';
		$total = $this->mod_gallery->count($where);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url.'view_list/'. $ga_id .'/'. $g_type .'/'. $pagelimit .'/';
		$config['suffix'] = "" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 7;
		$this->pagination->initialize($config);
		$maindata = $this->mod_gallery->get_list('*', $where, $pagelimit, $offset, $order);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('list.htm');
	}


	function add($ga_id=0) {
		$this->sci->assign('ga_id' , $ga_id);
		$this->load->library('form_validation');
		$this->_set_rules();
		$album = $this->mod_gallery_album->get_by_id($ga_id);
		$this->sci->assign('album' , $album);

		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('add.htm');
		}else{
			$this->image_directory = "userfiles/gallery/";
			$this->thumb_directory = "userfiles/gallery/thumb/";
			$this->thumb_width = 150;
			$this->thumb_height = 150;

			$filename = $this->_upload_image('g_file');
			if($filename) {
				$data['g_file'] = $filename;
			}
			$filename = $this->_upload_image('g_file2');
			if($filename) {
				$data['g_file2'] = $filename;
			}
			$data['ga_id'] = $ga_id;
			$data['g_title'] = $this->input->post('g_title');
			$data['g_slug'] = str_replace(' ','_', xss_clean($this->input->post('g_title')));
			$data['g_desc'] = $this->input->post('g_desc');
			$data['g_type'] = $this->input->post('g_type');
			$data['g_origin'] = $this->input->post('g_origin');
			$data['g_external'] = $this->input->post('g_external');

			if( !$this->mod_gallery->insert($data) ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url."view_list/$ga_id");
		}
	}

	function edit($g_id=0) {
		$gallery = $this->mod_gallery->get_by_id($g_id);
		$this->sci->assign('gallery' , $gallery);

		$ga_id = $gallery['ga_id'];
		$album = $this->mod_gallery_album->get_by_id($ga_id);
		$this->sci->assign('album' , $album);
		$this->sci->assign('ga_id' , $ga_id);

		$this->load->library('form_validation');
		$this->_set_rules();
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da('edit.htm');
		}else{
			$this->image_directory = "userfiles/gallery/";
			$this->thumb_directory = "userfiles/gallery/thumb/";
			$this->thumb_width = 150;
			$this->thumb_height = 150;

			if($_FILES['g_file'] == '') {
				$data['g_file'] = $this->input->post('g_file_default');
			} else {
				$filename = $this->_upload_image('g_file');
				if($filename) { $data['g_file'] = $filename; }
			}
			if($_FILES['g_file2'] == '') {
				$data['g_file2'] = $this->input->post('g_file2_default');
			} else {
				$filename = $this->_upload_image('g_file2');
				if($filename) { $data['g_file2'] = $filename; }
			}

			if($this->input->post('g_file2_remove')){ $data['g_file2'] = ''; }
			$data['ga_id'] = $ga_id;
			$data['g_title'] = $this->input->post('g_title');
			$data['g_slug'] = str_replace(' ','_', xss_clean($this->input->post('g_title')));
			$data['g_desc'] = $this->input->post('g_desc');
			$data['g_type'] = $this->input->post('g_type');
			$data['g_origin'] = $this->input->post('g_origin');
			$data['g_external'] = $this->input->post('g_external');

			if( !$this->mod_gallery->update($g_id, $data) ) {
				$this->session->set_confirm(0);
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->mod_url."view_list/$ga_id");
		}
	}

	function _set_rules() {
		$this->form_validation->set_rules('g_title', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('g_desc', 'Desc', 'trim|xss_clean');
		$this->form_validation->set_rules('g_type', 'Type', 'trim|xss_clean');
		$this->form_validation->set_rules('g_origin', 'Origin', 'trim|xss_clean');
		$this->form_validation->set_rules('g_external', 'External', 'trim|xss_clean');
		$this->form_validation->set_rules('g_file_default', 'Default Image', 'trim|xss_clean');
		$this->form_validation->set_rules('g_file2_remove', 'Default Image', 'trim|xss_clean');
	}

	function delete($g_id=0) {
		$gallery = $this->mod_gallery->get_by_id($g_id);
		if( !$this->mod_gallery->delete($g_id) ) {
		  $this->session->set_confirm(0);
		} else {
		  $this->session->set_confirm(1);
		}
		redirect($this->mod_url."view_list/".$gallery['ga_id']);
	}

	function album_delete($ga_id=0) {
		if( !$this->mod_gallery_album->delete($ga_id) ) {
		  $this->session->set_confirm(0);
		} else {
		  $this->session->set_confirm(1);
		}
		redirect($this->mod_url."album_view_list");
	}

}
