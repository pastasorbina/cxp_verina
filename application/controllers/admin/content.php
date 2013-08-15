<?php
class Content extends MY_Controller {

	var $mod_title = '';
	var $available_position = array();
	var $option = array();

	var $table_name = 'content';
	var $id_field = 'c_id';
	var $status_field = 'c_status';
	var $entry_field = 'c_entry';
	var $stamp_field = 'c_stamp';
	var $deletion_field = 'c_deletion';
	var $order_field = 'c_date';

	var $search_in = array('c_title','c_content_full','c_content_intro');


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->userinfo = $this->session->get_userinfo();

		$this->load->model('mod_content');
		$this->load->model('mod_media');
		$this->load->model('mod_media_relation');
		$this->load->model('mod_media_category');

		$this->db->where('t_status' , 'Active');
		$res = $this->db->get('content_tag');
		$all_content_tag = $res->result_array();
		$this->sci->assign('all_content_tag' , $all_content_tag);

	}

	function _join_setting() {
		$this->db->join('content_tag ct' , 'ct.t_id = content.t_id' , 'left');
	}


	function index( $cl_id=0, $c_status="Active", $c_publish_status='All', $c_is_featured='All', $pagelimit=20, $offset=0, $orderby='', $ascdesc='', $encodedkey='' ) {

		$this->session->set_bread('content-list');
		$this->session->set_bread('list');
		if($orderby == '') { $orderby = 'c_date'; }

		$this->db->where('cl_id' , $cl_id);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->sci->assign('content_label' , $content_label);
		$this->sci->assign('cl_id' , $cl_id);


		if($orderby == '') { $orderby = $content_label['cl_d_ordby']; }
		if($ascdesc == '') { $ascdesc = $content_label['cl_d_orddesc']; }


		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->sci->assign('c_status' , $c_status);
		$this->sci->assign('c_publish_status' , $c_publish_status);
		$this->sci->assign('c_is_featured' , $c_is_featured);

		$this->db->start_cache();
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_id' , $cl_id);
		$this->db->where( $this->status_field , $c_status);
		if($c_publish_status != 'All') {
			$this->db->where('c_publish_status' , $c_publish_status);
		}
		if($c_is_featured != 'All') {
			$this->db->where('c_is_featured' , $c_is_featured);
		}
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
		$config['base_url'] = $this->mod_url."index/$cl_id/$c_status/$c_publish_status/$c_is_featured/$pagelimit/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 9;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$this->_join_setting();
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->where('u_id' , $tmp['c_author'] );
			$res = $this->db->get('user');
			$maindata[$k]['author'] = $res->row_array();
			$this->db->where('c_id' , $tmp['c_parent_id'] );
			$res = $this->db->get('content');
			$maindata[$k]['parent'] = $res->row_array();
			//tags
			$this->db->where('c_id' , $tmp['c_id']);
			$res = $this->db->get('content_to_tag');
			$maindata[$k]['tags'] = $res->result_array();

			//gallery
			$this->db->where('c_id' , $tmp['c_id']);
			$this->db->where('cg_status' , 'Active');
			$this->db->order_by('cg_order' , 'asc');
			$res = $this->db->get('content_gallery');
			$maindata[$k]['gallery'] = $res->result_array();
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );



		//get all matches
		if(TRUE) {
			$this->db->where('b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->like('c_content_full' , 'http://localhost/');
			$this->db->order_by('c_id');
			$res = $this->db->get('content');
			$allcontent = $res->result_array();
			$matches = array();
			foreach($allcontent as $k=>$tmp) {
				$haystack = $tmp['c_content_full'];
				//$haystack = strip_tags($haystack);
				$ack = '';
				//preg_match('@^(?:http://localhost/)?([^/]+)@i', $haystack, $ack);
				//preg_match('/src="([^"]*)"/', $haystack, $ack);
				//preg_match('(src="[^"]*")', $haystack, $ack);
				//preg_match('/<img[^>]+>/i', $haystack, $ack);
				//preg_match('/(<img[^>]*src="[^>]*")/i', $haystack, $ack);


				//print_r($ack);
				//print "<br><br>";
				//print($tmp['c_title']);
				//print(($haystack));
				//print "<hr>";
				//if($ack) {
				//	if(!in_array($ack[1], $matches) ) {
				//		$matches[] = $ack[1];
				//	}
				//}

			}
			$this->sci->assign('matches' , $matches);
			$this->sci->assign('http_host' , 'http://'.$_SERVER['HTTP_HOST']);
		}

		$this->sci->da('index.htm');
	}


	function search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$c_status = $this->input->post('c_status');
		$c_publish_status = $this->input->post('c_publish_status');
		$c_is_featured = $this->input->post('c_is_featured');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$c_status/$c_publish_status/$c_is_featured/$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}

	function change_featured($c_id=0, $c_is_featured='No'){
		$this->db->where('c_id' , $c_id);
		$this->db->set('c_is_featured' , $c_is_featured);
		$this->db->update('content');
		redirect($this->session->get_bread('content-list'));
	}

	function change_publish_status($c_id=0, $c_publish_status='Published'){
		$this->db->where('c_id' , $c_id);
		$this->db->set('c_publish_status' , $c_publish_status);
		$this->db->update('content');
		redirect($this->session->get_bread('content-list'));
	}

	function change_status($c_id = 0, $status = 'Active'){
		$this->db->where('c_id' , $c_id);
		$this->db->set('c_status' , $status);
		$this->db->update('content');
		redirect($this->session->get_bread('content-list'));
	}


	function add( $cl_id=0, $parent_id=0 ) {
		//assign parent id
		$this->sci->assign('parent_id' , $parent_id);
		//get selected parent
		$this->db->where('c_id' , $parent_id);
		$res = $this->db->get('content');
		$content_parent = $res->row_array();
		$this->sci->assign('content_parent' , $content_parent);

		//get content label
		$this->sci->assign('cl_id' , $cl_id);
		$this->db->where('cl_id' , $cl_id);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->sci->assign('content_label' , $content_label);

		//get all content labels
		$this->db->where('cl_status' , 'Active');
		$res = $this->db->get('content_label');
		$all_content_label = $res->result_array();
		$this->sci->assign('all_content_label' , $all_content_label);



		//set available pos
		$this->_set_available_pos($content_label['cl_type']);

		$this->load->library('form_validation');
		$this->_set_rules();

		if($this->form_validation->run() == FALSE ) {
			//get all parent
			$this->db->where('cl_id' , $cl_id);
			$this->db->where('b_id' , $this->branch_id );
			$this->db->where('c_status' , 'Active');
			$res = $this->db->get('content');
			$all_parent = $res->result_array();
			$this->sci->assign('all_parent' , $all_parent);

			$this->sci->assign('available_position' , $this->available_position);
			$this->sci->assign('option' , $this->option);

			$this->sci->da('add.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);

			$this->_set_db();
			//if(isset($_POST['save_draft'])){ $save_status = 'Draft'; } else { $save_status = 'Published'; }
			$save_status = isset($_POST['save_draft']) ? 'Draft' : 'Published';
			if($save_status == 'Published') { $this->db->set('c_publish_date' , date('Y-m-d H:i:s') ); }
			$this->db->set('c_entry' , date('Y-m-d H:i:s') );
			$this->db->set('c_author' , $this->userinfo['u_id']);

			$this->db->set('c_publish_status' , $save_status );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();

			//insert media and option
			$this->_insert_media($insert_id);
			$this->_insert_option($insert_id);
			$this->_insert_tag($insert_id);

			//log history as create
			$this->_log($insert_id, 'create', $save_status);


			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
			}
			redirect($this->session->get_bread('content-list'));
		}
	}

	function edit($id=0) {
		$this->config->set_item('global_xss_filtering', FALSE);

		//get content data
		$this->_join_setting();
		$this->db->where('c_id' , $id);
		$this->db->where('c_status' , 'Active' );
		$this->db->order_by('c_entry' , 'DESC');
		$res = $this->db->get('content');
		$content = $res->row_array();

		//tags
		$this->db->join('content_tag' , 'content_tag.t_id = content_to_tag.t_id' , 'left');
		$this->db->where('c_id' , $content['c_id']);
		$res = $this->db->get('content_to_tag');
		$content_tags = $res->result_array();
		$this->sci->assign('content_tags' , $content_tags);

		$this->sci->assign('parent_id' , $content['c_parent_id']);
		//get selected parent
		$this->db->where('c_id' , $content['c_parent_id']);
		$res = $this->db->get('content');
		$content_parent = $res->row_array();
		$this->sci->assign('content_parent' , $content_parent);

		$this->db->where('cl_id' , $content['cl_id']);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->sci->assign('content_label' , $content_label);
		$this->_set_available_pos($content_label['cl_type']);

		//get all content labels
		$this->db->where('cl_status' , 'Active');
		$res = $this->db->get('content_label');
		$all_content_label = $res->result_array();
		$this->sci->assign('all_content_label' , $all_content_label);

		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');
		if($this->form_validation->run() == FALSE ) {
			//assign all parent
			$this->_join_setting();
			$this->db->where('cl_id' , $content['cl_id']);
			$this->db->where('b_id' , $this->branch_id );
			$this->db->where('c_status' , 'Active');
			$res = $this->db->get('content');
			$all_parent = $res->result_array();
			$this->sci->assign('all_parent' , $all_parent);

			$available_position = $this->available_position;
			$option = $this->option;

			switch($content['c_publish_status']) {
				case 'Draft' :
					// if draft, get data from history
					$this->db->where('c_id' , $id);
					$this->db->where('c_publish_status' , 'Draft');
					$this->db->where('ch_status' , 'Active' );
					$this->db->order_by('ch_entry' , 'DESC');
					$res = $this->db->get('content_history');
					$content_hist = $res->row_array();

					//override content data
					$content['c_title'] = $content_hist['c_title'];
					$content['c_content_full'] = $content_hist['c_content_full'];
					$content['c_content_intro'] = $content_hist['c_content_intro'];
					$content['c_date'] = $content_hist['c_date'];
					$media = unserialize($content_hist['c_media']);
					foreach($available_position as $k=>$tmp) {
						if(isset($media[$tmp['pos']])) {
							$m_id =  $media[$tmp['pos']];
							$this->db->where('media.b_id' , $this->branch_id);
							$this->db->where('mr_module' , 'content');
							$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
							$this->db->where('media.m_id' , $m_id);
							$this->db->where('mr_foreign_id' , $id);
							$this->db->where('mr_pos' , $tmp['pos']);
							$res = $this->db->get('media_relation');
							$result = $res->row_array();
							$available_position[$k]['data'] = $result;
						}
					}
					$get_option = unserialize($content_hist['c_option']);
					foreach($option as $k=>$tmp) {
						$this->db->where('content_option.b_id' , $this->branch_id);
						$this->db->where('c_id' , $id);
						$this->db->where('co_key' , $tmp['key']);
						$this->db->where('co_status' , 'Active');
						$this->db->order_by('co_stamp' , 'DESC');
						$res = $this->db->get('content_option');
						$result = $res->row_array();
						$option[$k]['data'] = $result;
						$co_value =  $get_option[$tmp['key']];
						$option[$k]['data']['co_value'] = $co_value;
					}
					break;

				case 'Published' :
					foreach($available_position as $k=>$tmp) {
						$this->db->where('media.b_id' , $this->branch_id);
						$this->db->where('mr_module' , 'content');
						$this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
						//$this->db->where('mr_status' , 'Active' );
						$this->db->where('mr_foreign_id' , $id);
						$this->db->where('mr_pos' , $tmp['pos']);
						$res = $this->db->get('media_relation');
						$result = $res->row_array();
						$available_position[$k]['data'] = $result;
					}
					foreach($option as $k=>$tmp) {
						$this->db->where('content_option.b_id' , $this->branch_id);
						$this->db->where('co_key' , $tmp['key']);
						$this->db->where('c_id' , $id);
						$this->db->where('co_status' , 'Active');
						$this->db->order_by('co_stamp' , 'DESC');
						$res = $this->db->get('content_option');
						$option[$k]['data'] = $res->row_array();
					}
					break;
			}

			$this->sci->assign('content' , $content);
			$this->sci->assign('available_position' , $available_position);
			$this->sci->assign('option' , $option);

			$this->sci->da('edit.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);

			if(isset($_POST['save_draft'])){ $save_status = 'Draft'; } else { $save_status = 'Published'; }
			switch($save_status) {
				case 'Draft' :
					$this->db->set('c_publish_status' , $save_status );
					$this->db->set('c_editor' , $this->userinfo['u_id']);
					$this->db->where('c_id' , $id);
					$ok = $this->db->update('content');
					$this->db->set('c_id' , $id);
					$this->db->set('c_publish_status' , $save_status );
					$ok = $this->_log($id, 'update', $save_status);

					$this->_insert_media($id);
					$this->_insert_option($id);
					$this->_insert_tag($id);

					break;
				case 'Published' :
					$this->_set_db();
					$this->db->set('c_editor' , $this->userinfo['u_id']);
					$this->db->set('c_publish_date' , date('Y-m-d H:i:s') );
					$this->db->set('c_publish_status' , $save_status );
					$this->db->where('c_id' , $id);
					$ok = $this->db->update('content');

					$this->_insert_media($id);
					$this->_insert_option($id);
					$this->_insert_tag($id);

					break;
			}

			if(!$ok) { $this->session->set_confirm(0); } else { $this->session->set_confirm(1); }
			//redirect($this->mod_url."edit/$id" );
			redirect($this->session->get_bread('content-list'));
		}
	}



	function change_lock($c_id=0) {
		$this->db->where('c_id' , $c_id);
		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('content');
		$result = $res->row_array();
		if(!$result) { redirect($this->session->get_bread('list') ); }

		$changeto = "Open";
		switch($result['c_lock_status']) {
			case 'Locked' 	: $changeto = "Open"; break;
			case 'Open' 	: $changeto = "Locked"; break;
			default			: break;
		}
		$this->db->set('c_lock_status' , $changeto);
		$this->db->where('c_id' , $c_id);
		$this->session->set_confirm( $this->db->update('content') );
		redirect($this->session->get_bread('list') );
	}


	function set_as_page($c_id=0) {
		//get page linked to this content

	}
/****************** IDLES *********************/



	function _set_rules() {
		$this->form_validation->set_rules('c_title', 'Title', 'trim|required|xss_clean');
		$this->form_validation->set_rules('c_content_full', 'Content', 'trim');
		$this->form_validation->set_rules('c_content_intro', 'Content Intro', 'trim');
		$this->form_validation->set_rules('c_parent_id', 'Parent ID', 'trim');
		$this->form_validation->set_rules('c_date', 'Date', 'trim');
		$this->form_validation->set_rules('cl_id', 'Label ID', 'trim');
		$this->form_validation->set_rules('cl_code', 'Content Code', 'trim');
		$this->form_validation->set_rules('c_is_featured', 'Is Featured', 'trim');

		$this->form_validation->set_rules('save_status', 'Save Status', 'trim');

		$this->form_validation->set_rules('co_id[]', 'Option ID', 'trim');
		$this->form_validation->set_rules('co_key[]', 'Option Key', 'trim');
		$this->form_validation->set_rules('co_value[]', 'Option Value', 'trim');

		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');

		$this->form_validation->set_rules('tags[]', 'Tags', 'trim');
	}

	function _set_db() {
		$this->db->set('b_id' , $this->branch_id );

		$c_title = $this->input->post('c_title');
		$this->db->set('c_title' , $c_title);

		$c_code = $this->input->post('c_code');
		if($c_code != '') {
			$this->db->set('c_code' , $c_code );
		} else {
			$this->db->set('c_code' , make_slug($c_title) );
		}

		$this->db->set('c_content_full' , $this->input->post('c_content_full') );
		$this->db->set('c_content_intro' , $this->input->post('c_content_intro') );
		$this->db->set('c_parent_id' , $this->input->post('c_parent_id') );
		$this->db->set('c_is_featured' , $this->input->post('c_is_featured') );
		$this->db->set('t_id' , $this->input->post('t_id') );
		$this->db->set('cl_id' , $this->input->post('cl_id') ); 

		$cl_id = $this->input->post('cl_id');
		$this->db->where('cl_id' , $cl_id);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->sci->assign('content_label' , $content_label);
		if($content_label['cl_code'] == 'page') {
			$width = 680;
			$height = 450;
		} elseif($content_label['cl_type'] == 'article') {
			$width = 515;
			$height = 340;
		} elseif($content_label['cl_code'] == 'tireology') {
			$width = 0;
			$height = 0;
		} else {
			$width = 0;
			$height = 0;
		}

		//$cl_id = $this->input->post('cl_id');
		//$cl_id = $cl_id ? $cl_id : 0;
		//$this->db->set('cl_id' ,  $cl_id);

		$c_date = $this->input->post('c_date');
		$c_date = $c_date ? $c_date : date('Y-m-d H:i:s');
		$this->db->set('c_date' , $c_date );

		if($_FILES['c_banner']['name'] != '' ) {
			$filename = $this->_upload_image('c_banner', TRUE, $width, $height);
			$this->db->set('c_banner' , $filename);
		}
	}

	function _log( $id=0, $action='update', $save_status='Draft') {
		$this->db->where('c_id' , $id);
		$res = $this->db->get('content');
		$content = $res->row_array();

		if($action == 'create' || ($action == 'update' && $save_status == 'Published') ) {
			$this->db->set('c_id' , $id);

			$this->db->set('b_id' , $content['b_id'] );
			$this->db->set('cl_id' , $content['cl_id'] );

			$this->db->set('c_title' , $content['c_title'] );
			$this->db->set('c_code' , $content['c_code'] );
			$this->db->set('c_content_full' , $content['c_content_full'] );
			$this->db->set('c_content_intro' , $content['c_content_intro'] );
			$this->db->set('c_date' , $content['c_date'] );

			$this->db->set('c_publish_date' , $content['c_publish_date'] );
			$this->db->set('c_publish_status' , $save_status );
		}

		if($action == 'update' && $save_status == 'Draft') {
			$this->db->set('b_id' , $this->branch_id );

			$c_title = $this->input->post('c_title');
			$this->db->set('c_title' , $c_title);
			$this->db->set('c_code' , make_slug($c_title) );
			$this->db->set('c_content_full' , $this->input->post('c_content_full') );
			$this->db->set('c_content_intro' , $this->input->post('c_content_intro') );

			$cl_id = $this->input->post('cl_id');
			$cl_id = $cl_id ? $cl_id : 0;
			$this->db->set('cl_id' ,  $cl_id);

			$c_date = $this->input->post('c_date');
			$c_date = $c_date ? $c_date : date('Y-m-d H:i:s');
			$this->db->set('c_date' , $c_date );

			$this->db->set('c_publish_date' , $content['c_publish_date'] );
		}

		$m_id = $this->input->post('m_id');
		$pos = $this->input->post('mr_pos');
		foreach($pos as $k=>$tmp) { $media[$pos[$k]] = $m_id[$k] ? $m_id[$k] : '0'; }
		$this->db->set('c_media' , serialize($media));

		$co_id = $this->input->post('co_id');
		$co_key = $this->input->post('co_key');
		$co_value = $this->input->post('co_value');
		if(is_array($co_key)) { foreach($co_key as $k=>$tmp) { $option[$co_key[$k]] = $co_value[$k] ? $co_value[$k] : ''; } }
		if($option){ $this->db->set('c_option' , serialize($option)); }

		$this->db->set('u_id' , $this->userinfo['u_id']);
		$this->db->set('ch_action' , $action);
		$this->db->set('ch_entry' , date('Y-m-d H:i:s') );
		$this->db->insert('content_history');

		return $this->db->insert_id();
	}



	//upload image for content
	function _upload_image( $fieldname = '', $crop = FALSE, $width=0, $height=0 ) {
		$dir_target = './userfiles/upload/';

		$filename = FALSE;
		if (!empty($_FILES)) {
			if ($_FILES[$fieldname]['tmp_name']) {
				$code = uniqid();
				$realFile = $_FILES[$fieldname]['name'];
				$extension = end(explode(".", $realFile));
				$tempFile = $_FILES[$fieldname]['tmp_name'];
				$filename = $code . '.' . $extension;

				$targetFile =  $dir_target . $filename;
				move_uploaded_file($tempFile, $targetFile);

				if($width !=0 && $height !=0) {
					//resize the image
					$config['width'] = $width;
					$config['height'] = $height;
					$config['image_library'] = 'gd2';
					$config['source_image'] = $targetFile;
					$config['new_image'] = $targetFile;
					$config['maintain_ratio'] = TRUE;
					$this->load->library('image_lib');
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
					$this->image_lib->clear();
				}


				//make thumbnail
				$dir_thumb = './userfiles/upload/thumb/';
				$targetThumb = $dir_thumb.$filename;
				$config['width'] = 200;
				$config['image_library'] = 'gd2';
				$config['source_image'] = $targetFile;
				$config['new_image'] = $targetThumb;
				$config['maintain_ratio'] = TRUE;
				$this->load->library('image_lib');
				$this->image_lib->initialize($config);
				$this->image_lib->resize();
				$this->image_lib->clear();
			}
		}
		return $filename;
	}



	function _insert_tag( $id=0, $action='update' ) {
		$tags = $this->input->post('tags');
		$this->db->where('c_id' , $id);
		$this->db->delete('content_to_tag');
		foreach($tags as $k=>$tmp) {
			$this->db->set('c_id' , $id);
			$this->db->set('t_id' , $tmp);
			$this->db->insert('content_to_tag');
		}
	}




	function _insert_media( $id=0, $action='update' ) {
		//insert/update media
		$m_id = $this->input->post('m_id');
		$mr_id = $this->input->post('mr_id');
		$mr_pos = $this->input->post('mr_pos');
		//TODO:insert validation here

		//print_r($_POST);
		//exit();
		if($mr_pos) {
			foreach($mr_pos as $k=>$tmp) {
				//get current media related to this item
				$result = array();
				$this->db->where('mr_id' , $mr_id[$k]);
					$this->db->order_by('mr_stamp' , 'DESC');
					$res = $this->db->get('media_relation');
					$result = $res->row_array();

					$this->db->where('mr_id' , $mr_id[$k]);
					$this->db->set('mr_status' , 'Deleted');
					$this->db->update('media_relation');

					if($m_id[$k]) {
					$this->db->set('m_id' , $m_id[$k] );
					$this->db->set('mr_pos' , $mr_pos[$k] );
					$this->db->set('mr_foreign_id' , $id );
					$this->db->set('mr_module' , $this->mod );
					$this->db->set('b_id' , $this->branch_id);
					$this->db->set('mr_status' , 'Active');
					if( $result ) {
						$this->db->where('mr_id' , $mr_id[$k] );
						$this->db->update('media_relation');
					} else {
						$this->db->set('mr_entry' , date('Y-m-d H:i:s') );
						$this->db->insert('media_relation');
					}
				}
			}
		}
	}

	function _insert_option($id=0, $action='update') {
		$co_id = $this->input->post('co_id');
		$co_key = $this->input->post('co_key');
		$co_value = $this->input->post('co_value');
		if(is_array($co_id)){
		foreach($co_id as $k=>$tmp) {
			$this->db->where('co_id' , $co_id[$k]);
			$this->db->where('co_status' , 'Active');
			$this->db->order_by('co_stamp' , 'DESC');
			$res = $this->db->get('content_option');
			$result = $res->row_array();
			$this->db->set('c_id' , $id);
			$this->db->set('co_key' , $co_key[$k]);
			$this->db->set('co_value' , $co_value[$k]);
			$this->db->set('b_id' , $this->branch_id);
			if($result) {
				$this->db->where('co_id' , $co_id[$k]);
				$this->db->update('content_option');
			} else {
				$this->db->set('co_entry' , date('Y-m-d H:i:s') );
				$this->db->insert('content_option');
			}
		}
		}
	}

	function _set_available_pos($cl_type='') {
		switch($cl_type) {
			case 'page' :
				$this->available_position[] = array('pos'=>'top_banner', 'name'=>'Top Banner');
				$this->available_position[] = array('pos'=>'left_banner', 'name'=>'Left Banner');
				$this->option[] = array('key'=>'side_text', 'name'=>'Side Text');
				break;
			case 'article' :
				$this->available_position[] = array('pos'=>'image', 'name'=>'Featured Image');
				break;
		}
	}


	function replace_img_url($cl_id=0) {
		$local_url = $this->input->post('local_url');
		$remote_url = $this->input->post('remote_url');

		$this->db->where('b_id' , $this->branch_id);
		$this->db->like('c_content_full' , $local_url);
		$res = $this->db->get('content');
		$all = $res->result_array();

		$matches = array();
		foreach($all as $k=>$tmp) {
			$str = $tmp['c_content_full'];
			$str = str_replace($local_url, $remote_url, $str);
			$this->db->where('c_id' , $tmp['c_id']);
			$this->db->set('c_content_full' , $str);
			$this->db->update('content');
		}

		redirect($this->mod_url.'index/'.$cl_id);
	}


	function list_size_chart( $c_status="Active", $pagelimit=20, $offset=0, $orderby='c_date', $ascdesc='DESC', $encodedkey='' ) {
		$this->session->set_bread('content-list');
		if($orderby == '') { $orderby = 'c_date'; }

		$this->db->where('cl_code' , 'size_chart');
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		$this->sci->assign('content_label' , $content_label);
		$cl_id = $content_label['cl_id'];
		$this->sci->assign('cl_id' , $cl_id);

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->sci->assign('c_status' , $c_status);

		$this->db->start_cache();
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_id' , $cl_id);
		$this->db->where( $this->status_field , $c_status);
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
		$config['base_url'] = $this->mod_url."list_size_chart/$c_status/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$this->_join_setting();
		$this->db->join('product_category pc' , 'pc.pc_id = content.pc_id' , 'left');
		$this->db->join('product_type pt' , 'pt.pt_id = content.pt_id' , 'left');
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->where('u_id' , $tmp['c_author'] );
			$res = $this->db->get('user');
			$maindata[$k]['author'] = $res->row_array();
			$this->db->where('c_id' , $tmp['c_parent_id'] );
			$res = $this->db->get('content');
			$maindata[$k]['parent'] = $res->row_array();
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('list_size_chart.htm');
	}

	function edit_size_chart($id=0) {
		$this->config->set_item('global_xss_filtering', FALSE);

		//get content data
		$this->_join_setting();
		$this->db->where('c_id' , $id);
		$this->db->where('c_status' , 'Active' );
		$this->db->order_by('c_entry' , 'DESC');
		$res = $this->db->get('content');
		$content = $res->row_array();

		$this->sci->assign('parent_id' , $content['c_parent_id']);
		//get selected parent
		$this->db->where('c_id' , $content['c_parent_id']);
		$res = $this->db->get('content');
		$content_parent = $res->row_array();
		$this->sci->assign('content_parent' , $content_parent);

		//get all content labels
		$this->db->where('cl_status' , 'Active');
		$res = $this->db->get('content_label');
		$all_content_label = $res->result_array();
		$this->sci->assign('all_content_label' , $all_content_label);

		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('pc_id', 'Product Category', 'trim');
		$this->form_validation->set_rules('pt_id', 'Product Type', 'trim');
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');
		if($this->form_validation->run() == FALSE ) {
			//assign all parent
			$this->_join_setting();
			$this->db->where('cl_id' , $content['cl_id']);
			$this->db->where('b_id' , $this->branch_id );
			$this->db->where('c_status' , 'Active');
			$res = $this->db->get('content');
			$all_parent = $res->result_array();
			$this->sci->assign('all_parent' , $all_parent);

			//get all product type
			$this->db->where('pt_status' , 'Active');
			$res = $this->db->get('product_type');
			$product_type = $res->result_array();
			$this->sci->assign('product_type' , $product_type);

			//get all product category
			$this->db->where('pc_status' , 'Active');
			$res = $this->db->get('product_category');
			$product_category = $res->result_array();
			$this->sci->assign('product_category' , $product_category);

			$this->sci->assign('content' , $content);
			$this->sci->da('edit_size_chart.htm');
		}else{
			$this->_set_db();
			$this->db->set('pc_id' , $this->input->post('pc_id') );
			$this->db->set('pt_id' , $this->input->post('pt_id') );
			$this->db->set('c_editor' , $this->userinfo['u_id']);
			$this->db->set('c_publish_date' , date('Y-m-d H:i:s') );
			$this->db->set('c_publish_status' , 'Published' );
			$this->db->where('c_id' , $id);
			$ok = $this->db->update('content');

			if(!$ok) { $this->session->set_confirm(0); } else { $this->session->set_confirm(1); }
			redirect($this->session->get_bread('content-list'));
		}

	}




	function ajax_list_select($cl_id=0, $c_parent_id=0, $pagenum = 1 ,
							  $pagelimit = '20',
							  $orderby='',
							  $ascdesc='DESC',
							  $encodedkey='') {
		$this->sci->assign('cl_id' , $cl_id);
		$this->sci->assign('c_parent_id' , $c_parent_id);
		$this->sci->assign('_post' , $_POST);

		$this->db->where('cl_id' , $cl_id);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		if($content_label) {$cl_code = $content_label['cl_code']; } else { $cl_code= 'page'; }

		if($cl_code == 'tireology') { $c_parent_id = 0; }
		//$c_parent_id = 0;

		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		if ($pagelimit == '') $pagelimit = $this->default_pagelimit;
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->join_setting();
			if($cl_id != 0) {
				$this->db->where('cl_id' , $cl_id);
			}
			$this->db->where('c_parent_id' , $c_parent_id);
			$this->where_setting();
			$this->select_setting();
		$this->db->stop_cache();

		$totaldata = $this->db->count_all_results($this->table_name);

		$totalpage = ceil($totaldata / $pagelimit);
		$pagenum = min($totalpage , $pagenum);
		$pagenum = max(1 , $pagenum);
		$offset = $pagelimit * ($pagenum - 1 );
		$this->sci->assign('totaldata' , $totaldata);
		$this->sci->assign('totalpage' , $totalpage);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('pagenum' , $pagenum);
		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		$this->sci->d('list_select.htm');
	}

	/**
	 * Ajax Filter
	 * @access public
	 *
	 */
	function ajax_filter() {
		$ret = array();
		$searchkey = $this->input->post('searchkey');
		$searchkey = safe_base64_encode($searchkey);

		$cl_id = $this->input->post('cl_id');
		$c_parent_id = $this->input->post('c_parent_id');
		$ascdesc = $this->input->post('ascdesc');
		$orderby = $this->input->post('orderby');
		$pagenum = $this->input->post('pagenum');
		$pagenum = 1;
		$pagelimit = $this->input->post('pagelimit');
		$page = $this->input->post('page');
		$uristring = $this->input->post('uristring');

		$uri = compact('page'.'cl_id'. 'c_parent_id','pagenum','pagelimit','orderby','ascdesc', 'searchkey');
		$href = implode('/', $uri);

		$ret['href'] = $href;
		$ret['status'] = 'ok';
		print json_encode($ret);
	}



	//////////////////?TAGS

	function ajax_list_select_tags($pagenum = 1 ,
							  $pagelimit = '20',
							  $orderby='',
							  $ascdesc='ASC',
							  $encodedkey='') {

		$this->sci->assign('_post' , $_POST);
		if ($orderby == '') $orderby = 't_name';
		if ($ascdesc == '') $ascdesc = 'ASC';
		if ($pagelimit == '') $pagelimit = $this->default_pagelimit;
		$this->db->start_cache();
			if($encodedkey != ''){
				$searchkey = safe_base64_decode($encodedkey);
				$this->db->or_like('t_name', $searchkey);
				$this->sci->assign('searchkey' , $searchkey);
			}
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->where('t_status' , 'Active');
		$this->db->stop_cache();

		$totaldata = $this->db->count_all_results('content_tag');

		$totalpage = ceil($totaldata / $pagelimit);
		$pagenum = min($totalpage , $pagenum);
		$pagenum = max(1 , $pagenum);
		$offset = $pagelimit * ($pagenum - 1 );
		$this->sci->assign('totaldata' , $totaldata);
		$this->sci->assign('totalpage' , $totalpage);
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('pagenum' , $pagenum);
		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('content_tag');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->enum_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		$this->sci->d('list_select_tags.htm');
	}

	function ajax_get_tag($t_id) {
		$this->db->where('t_id' , $t_id);
		$res = $this->db->get('content_tag');
		$tag = $res->row_array();
		echo json_encode($tag);
	}

	function ajax_add_tag(){
		$this->load->library('form_validation');
		$this->form_validation->set_rules('t_name', 'Tag', 'trim|xss_clean|required');
		if($this->form_validation->run() != TRUE) {} else {
			$this->db->where('t_status' , 'Active');
			$this->db->where('t_name' , $this->input->post('t_name'));
			$res = $this->db->get('content_tag');
			$tag = $res->row_array();
			if($tag) {
				print json_encode(intval($tag['t_id']));
			} else {
				$this->db->set('t_name' , $this->input->post('t_name'));
				$this->db->set('t_entry' , date('Y-m-d H:i:s'));
				$this->db->insert('content_tag');
				$id = $this->db->insert_id();
				print json_encode($id);
			}

		}
	}

	/**
	 * Ajax Filter
	 * @access public
	 *
	 */
	function ajax_filter_tags() {
		$ret = array();
		$searchkey = $this->input->post('searchkey');
		$searchkey = safe_base64_encode($searchkey);

		$ascdesc = $this->input->post('ascdesc');
		$orderby = $this->input->post('orderby');
		$pagenum = $this->input->post('pagenum');
		$pagenum = 1;
		$pagelimit = $this->input->post('pagelimit');
		$page = $this->input->post('page');
		$uristring = $this->input->post('uristring');

		$uri = compact('page','pagenum','pagelimit','orderby','ascdesc', 'searchkey');
		$href = implode('/', $uri);

		$ret['href'] = $href;
		$ret['status'] = 'ok';
		print json_encode($ret);
	}

	function get_newstag_option($nt_id=0) {
		$this->db->where('nt_status' , 'Active');
		$res = $this->db->get('content_newstag');
		$newstag = $res->result_array();
		$this->sci->assign('newstag' , $newstag);
		$this->sci->assign('nt_id' , $nt_id);
		$this->sci->d('newstag_option.htm');
	}

	function form_newstag() {
		$this->load->library('form_validation');
		$this->sci->d('newstag_form.htm');
	}

	function submit_add_newstag() {
		$nt_name = $this->input->post('nt_name');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('nt_name', 'Size', 'trim|required');
		if($this->form_validation->run() == FALSE ) {
			$data['status'] = 'error';
			$data['msg'] = strip_tags(validation_errors());
		}else{
			$this->db->set('nt_name' , $nt_name);
			$this->db->set('nt_entry' , date('Y-m-d H:i:s') );
			$this->db->insert('content_newstag');
			$nt_id = $this->db->insert_id();
			$data['nt_id'] = $nt_id;
			$data['status'] = 'ok';
			$data['msg'] = 'ok';
		}
		print json_encode($data);
	}




	////******* DATABASE FIXES ********/
	function import_news_a() {
		//get lable news
		$this->db->where('cl_code' , 'news');
		$res = $this->db->get('content_label');
		$label = $res->row_array();

		$this->db->order_by('tanggal_news' , 'DESC');
		$res = $this->db->get('tab_news');
		$tabnews = $res->result_array();
		//print_r($tabnews);

		foreach($tabnews as $k=>$tmp) {
			//$this->db->set('c_entry' , $tmp['tanggal_news']);
			//$this->db->set('c_date' , $tmp['tanggal_news']);
			//$this->db->set('c_show_banner' , 'No');
			//$this->db->set('c_banner' , $tmp['gambar']);
			//$this->db->set('c_title' , $tmp['judul_news']);
			//$this->db->set('c_content_intro' , $tmp['intro'], TRUE);
			//$this->db->set('c_content_full' , $tmp['intro'].$tmp['lanjutan'], TRUE);
			//$this->db->set('cl_id' , $label['cl_id']);
			//$this->db->set('c_code' , url_title($tmp['judul_news'],'underscore', TRUE), TRUE );
			//$this->db->set('c_author' , 18);
			//$this->db->set('c_publish_status' , 'Published');
			//$this->db->set('b_id' , 1);
			//$this->db->insert('content');

			//$upd = array();
			//$upd = array(
			//			 'c_entry' => $tmp['tanggal_news'],
			//			 'c_date' => $tmp['tanggal_news'],
			//			 'c_show_banner' => $tmp['tanggal_news'],
			//			 'c_banner' => $tmp['tanggal_news'],
			//			 'c_title' => $tmp['tanggal_news'],
			//			 'c_content_intro' => $tmp['tanggal_news'],
			//			 'c_content_full' => $tmp['tanggal_news'],
			//			 'cl_id' => $label['cl_id'],
			//			 'c_code' => url_title($tmp['judul_news'],'underscore', TRUE),
			//			 'c_author' => 18,
			//			 'c_publish_status' => 'Published',
			//			 'b_id' => 1,
			//
			//			 )

			$sql = "INSERT INTO content (c_entry, c_date, c_publish_date, c_show_banner, c_banner, c_title, c_content_intro, c_content_full, cl_id, c_code, c_author, c_publish_status, b_id) VALUES (
			'".$tmp['tanggal_news']."',
			'".$tmp['tanggal_news']."',
			'".$tmp['tanggal_news']."',
			'No',
			'".$tmp['gambar']."',
			'".$tmp['judul_news']."',
			'".$tmp['intro']."',
			'<p>".$tmp['intro']."</p>".$tmp['lanjutan']."',
			'".$label['cl_id']."',
			'".url_title($tmp['judul_news'],'underscore', TRUE)."',
			18,
			'Published',
			1);";
			$this->db->query($sql);
		}


	}


}
