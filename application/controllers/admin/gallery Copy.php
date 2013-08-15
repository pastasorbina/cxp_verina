<?php
class Gallery extends MY_Controller {

	//var $module = 'page';
	var $mod_title = 'Manage Gallery';
	var $available_position = array();
	var $option = array();

	var $table_name = 'gallery';
	var $id_field = 'g_id';
	var $status_field = 'g_status';
	var $entry_field = 'g_entry';
	var $stamp_field = 'g_stamp';
	var $deletion_field = 'g_deletion';
	var $order_field = 'g_date';

	var $search_in = array('g_title', 'g_desc');

	var $thumb_width = 200;
	var $thumb_height = 134;


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->load->model('mod_media');
		$this->load->library('media');

		$this->available_position[] = array('pos'=>'image', 'name'=>'Image');

		/*print $this->thumb_width;
		print $this->thumb_height*/;
		$this->CI->thumb_width = 200;
		$this->CI->thumb_height = 134;

	}


	function index( $ga_id=0, $pagelimit=15, $offset=0, $orderby='', $ascdesc='DESC', $encodedkey='' ) {
		$this->session->set_bread('list');
		if($orderby == '') { $orderby = $this->order_field; }

		//assign default filter params
		$this->sci->assign('ga_id' , $ga_id);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->db->where('ga_status' , 'Active');
		$this->db->where('b_id' , $this->branch_id );
		$res = $this->db->get('gallery_album');
		$gallery_album = $res->result_array();
		$this->sci->assign('gallery_album' , $gallery_album);

		$this->db->start_cache();
		$this->db->join('user' , 'gallery.g_author_id = user.u_id' , 'left');
		$this->db->where('gallery.b_id' , $this->branch_id);
		$this->db->where('ga_id' , $ga_id);
		$this->db->where( $this->status_field , 'Active' );
		$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
		$this->db->order_by($orderbyconv , $ascdesc);
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			foreach($this->search_in as $k=>$tmp) {
				$this->db->or_like($tmp, $searchkey);
			}
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->stop_cache();

		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/$ga_id/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			foreach($this->available_position as $l=>$tmp2) {
				$result = $this->mod_media->get_media($this->mod, $tmp2['pos'], $tmp['g_id']);
				$maindata[$k][$tmp2['pos']] = $result;
			}
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('index.htm');
	}



	function add( $ga_id=0 ) {
		$this->sci->assign('ga_id' , $ga_id);
		$this->db->where('ga_id' , $ga_id);
		$res = $this->db->get('gallery_album');
		$gallery_album = $res->row_array();
		$this->sci->assign('gallery_album' , $gallery_album);

		$this->db->where('ga_status' , "Active");
		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('gallery_album');
		$all_album = $res->result_array();
		$this->sci->assign('all_album' , $all_album);

		$this->load->library('form_validation');
		$this->_set_rules();

		if($this->form_validation->run() == FALSE ) {
			//assign available position
			$this->sci->assign('available_position' , $this->available_position);

			$this->sci->da('add.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);

			$this->database_setting();
			$this->db->set('g_entry' , date('Y-m-d H:i:s') );
			$this->db->set('g_author_id' , $this->userinfo['u_id'] );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();

			//insert media and option
			//$this->_insert_media($insert_id);
			$g_type = $this->input->post('g_type');
			$m_id = $this->input->post('m_id');

			if( $m_id ) {

			}
			$this->_insert_media($insert_id);


			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				$ga_id = $this->input->post('ga_id');
				redirect($this->mod_url."index/$ga_id" );
			}

		}
	}

	function edit($id=0) {
		$this->config->set_item('global_xss_filtering', FALSE);

		//get data from history's last update
		$this->db->where('g_id' , $id);
		$this->db->where('g_status' , 'Active' );
		$this->db->order_by('g_date' , 'DESC');
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);

		$this->sci->assign('ga_id' , $data['ga_id'] );
		$this->db->where('ga_id' , $data['ga_id']);
		$res = $this->db->get('gallery_album');
		$gallery_album = $res->row_array();
		$this->sci->assign('gallery_album' , $gallery_album);

		$this->db->where('ga_status' , "Active");
		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('gallery_album');
		$all_album = $res->result_array();
		$this->sci->assign('all_album' , $all_album);

		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');

		if($this->form_validation->run() == FALSE ) {

			//assign available position
			$available_position = $this->available_position;

			foreach($available_position as $k=>$tmp) {
				$this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
				$this->db->where('mr_status' , 'Active' );
				$this->db->where('mr_foreign_id' , $id);
				$this->db->where('mr_module' , $this->mod);
				$this->db->where('mr_pos' , $tmp['pos']);
				$res = $this->db->get('media_relation');
				$result = $res->row_array();
				$available_position[$k]['data'] = $result;
			}

			$this->sci->assign('available_position' , $available_position);

			$this->sci->da('edit.htm');
		}else{
			$this->database_setting();
			$this->db->where('g_id' , $id);
			$ok = $this->db->update($this->table_name);
			//$this->_insert_media($id);
			$this->media->insert_media($this->mod, $id);
			if(!$ok) { $this->session->set_confirm(0); } else { $this->session->set_confirm(1); }
			$ga_id = $this->input->post('ga_id');
			redirect($this->mod_url."index/$ga_id" );
		}
	}




	function _set_rules() {
		$this->form_validation->set_rules('g_title', 'Title', 'trim|xss_clean');
		$this->form_validation->set_rules('g_desc', 'Desc', 'trim');
		$this->form_validation->set_rules('g_date', 'Date', 'trim');
		$this->form_validation->set_rules('ga_id', 'Gallery Album', 'trim|required');

		$this->form_validation->set_rules('g_type', 'Type', 'trim');
		$this->form_validation->set_rules('g_data', 'Data', 'trim');
		//$this->form_validation->set_rules('g_data_file', 'Data', 'trim');

		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');
	}

	function database_setting() {
		$this->db->set('b_id' , $this->branch_id );
		$this->db->set('ga_id' ,  $this->input->post('ga_id') );
		$this->db->set('g_title' , $this->input->post('g_title'));
		$this->db->set('g_desc' , $this->input->post('g_desc') );

		$g_type = $this->input->post('g_type');
		$this->db->set('g_type' , $g_type);
		switch($g_type) {
			case 'video' :
				$this->db->set('g_data' , $this->input->post('g_data'));
				break;
			case 'file' :
				if($_FILES['g_data_file']['name'] != '' ) {
					$config['upload_path'] = 'userfiles/file/';
					$config['allowed_types'] = 'pdf|doc|jpg|gif|png|';
					//$config['max_size']	= '10000000000000000000000';
					$config['file_name'] = sanitize_filename(url_title(remove_symbols($_FILES['g_data_file']['name'])));
					$this->load->library('upload');
					$this->upload->initialize($config);

					$this->upload->do_upload('g_data_file');
					$uploaded = $this->upload->data();
					$filename = $uploaded['raw_name'].$uploaded['file_ext'];
					$this->db->set('g_data' , $filename);
				}


				//print $this->upload->display_errors();
				//exit();
				//$fieldname = 'g_data_file';
				//$upload_path = 'userfiles/file/';
				//if (!empty($_FILES)) {
				//	if ($_FILES[$fieldname]['tmp_name']) {
				//		$code = uniqid();
				//		$realFile = $_FILES[$fieldname]['name'];
				//		$filename = url_title($realFile);
				//		$targetFile =  $upload_path . $filename;
				//		if(move_uploaded_file($tempFile, $targetFile)) {
				//			$this->db->set('g_data' , $filename);
				//		}
				//	}
				//}
				break;
			default :
				break;
		}


		$g_date = $this->input->post('g_date');
		$g_date = $g_date ? $g_date : date('Y-m-d H:i:s');
		$this->db->set('g_date' , $g_date );
	}




}
