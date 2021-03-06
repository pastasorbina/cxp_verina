<?php
class Article extends MY_Controller {

	var $mod_title = 'Manage Articles';
	var $available_position = array();


	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->load->model('mod_content');
		$this->load->model('mod_media');
		$this->load->model('mod_media_relation');
		$this->load->model('mod_media_category');

		$this->available_position[] = array('pos'=>'top_banner', 'name'=>'Top Banner');
		$this->available_position[] = array('pos'=>'thumbnail', 'name'=>'Thumbnail');


	}

	function index() {
		$this->view_list();
	}


	function view_list( $c_status="Active", $pagelimit=10, $offset=0, $orderby='c_publish_date', $ascdesc='DESC', $encodedkey='' ) {
		$this->session->set_bread('list');

		//assign default filter params
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);

		//assign other filters
		$this->sci->assign('c_status' , $c_status);
		//print $this->mod ;

		$this->db->start_cache();
		if($encodedkey != ''){
			$searchkey = safe_base64_decode($encodedkey);
			$this->db->like('c_title', $searchkey);
			$this->db->or_like('c_content_full', $searchkey);
			$this->db->or_like('c_content_intro', $searchkey);
			$this->sci->assign('searchkey' , $searchkey);
		}
		$this->db->where('c_module' , $this->mod );
		$this->db->where('c_status' , $c_status);
		$this->db->order_by($orderby , $ascdesc);
		$this->db->stop_cache();

		$total = $this->db->count_all_results('content');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."view_list/$c_status/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 6;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('content');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->where('u_id' , $tmp['c_author_id'] );
			$res = $this->db->get('user');
			$maindata[$k]['author'] = $res->row_array();
			$this->db->where('u_id' , $tmp['c_lastedit_id'] );
			$res = $this->db->get('user');
			$maindata[$k]['lastedit'] = $res->row_array();
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->sci->da('list.htm');
	}

	function add() {
		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->assign('available_position' , $this->available_position);
			$this->sci->da('add.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);
			$this->db->set('c_title' , $this->input->post('c_title'));
			$this->db->set('c_content_full' , $this->input->post('c_content_full'));
			$this->db->set('c_module' , $this->mod);
			$this->db->set('c_lastedit_id' , $this->userinfo['u_id']);
			$this->db->set('c_author_id' , $this->userinfo['u_id']);
			$this->db->set('c_publish_status' , 'Published');
			$this->db->set('c_publish_date' , date('Y-m-d H:i:s') );

			if(!$this->db->insert('content')) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$content_insert_id = $this->db->insert_id();
				$m_id_arr = $this->input->post('m_id');
				$mr_pos_arr = $this->input->post('mr_pos');
				foreach($m_id_arr as $k=>$tmp) {
					$this->db->set('m_id' , $m_id_arr[$k] );
					$this->db->set('mr_pos' , $mr_pos_arr[$k] );
					$this->db->set('mr_foreign_id' , $content_insert_id );
					$this->db->set('mr_module' , $this->mod );
					$this->db->insert('media_relation');
					//$this->mod_media_relation->insert($data);
				}
				$this->session->set_confirm(1);
				redirect($this->mod_url.'view_list');
			}

		}
	}

	function edit($c_id=0) {

		$this->db->where('c_id' , $c_id);
		$res = $this->db->get('content');
		$content = $res->row_array();
		$this->sci->assign('content' , $content);

		$this->load->library('form_validation');
		$this->_set_rules();
		$this->form_validation->set_rules('m_id[]', 'Media', 'trim');
		$this->form_validation->set_rules('mr_pos[]', 'Media Position', 'trim');
		$this->form_validation->set_rules('remove_mr_id[]', 'Remove Media', 'trim');
		if($this->form_validation->run() == FALSE ) {
			//assign available position
			$available_position = $this->available_position;
			foreach($available_position as $k=>$tmp) {
				$this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
				$this->db->where('mr_status' , 'Active' );
				$this->db->where('mr_foreign_id' , $c_id);
				$this->db->where('mr_pos' , $tmp['pos']);
				$res = $this->db->get('media_relation');
				$available_position[$k]['data'] = $res->row_array();
			}
			$this->sci->assign('available_position' , $available_position);

			//assign content history
			$this->db->where('c_id' , $c_id);
			$this->db->where('c_module' , $this->mod);
			$this->db->order_by('ch_stamp' , 'DESC');
			$this->db->limit(5, 0);
			$res = $this->db->get('content_history');
			$history = $res->result_array();
			$this->sci->assign('history' , $history);

			$this->sci->da('edit.htm');
		}else{
			$this->config->set_item('global_xss_filtering', FALSE);
			$this->_set_db();
			$this->db->where('c_id' , $c_id);
			if( !$this->db->update('content') ) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'edit');
			} else {
				$this->mod_content->_log($c_id, 'Update');
				$m_id_arr = $this->input->post('m_id');
				$mr_id_arr = $this->input->post('mr_id');
				$mr_pos_arr = $this->input->post('mr_pos');
				foreach($mr_id_arr as $k=>$tmp) {
					$this->db->where('mr_id' , $tmp);
					$res = $this->db->get('media_relation');
					$result = $res->row_array();
					$this->db->set('m_id' , $m_id_arr[$k] );
					$this->db->set('mr_pos' , $mr_pos_arr[$k] );
					$this->db->set('mr_foreign_id' , $c_id );
					$this->db->set('mr_module' , $this->mod );
					if( $result ) {
						$this->db->where('mr_id' , $tmp);
						$this->db->update('media_relation');
					} else {
						$this->db->insert('media_relation');
					}
				}

				$this->session->set_confirm(1);
				redirect($this->mod_url.'edit/'.$c_id);
			}

		}
	}

	function delete($c_id=0) {
		$this->change_status($c_id, 'Deleted');
	}

	function change_lock($c_id=0) {
		$this->db->where('c_id' , $c_id);
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
		$this->mod_content->_log($c_id, 'Lock');
		redirect($this->session->get_bread('list') );
	}

	function change_status($c_id=0, $c_status="Active") {
		$this->db->where('c_id' , $c_id);
		$res = $this->db->get('content');
		$result = $res->row_array();
		if(!$result) { redirect($this->session->get_bread('list') ); }

		$this->db->set('c_status' , $c_status);
		$this->db->where('c_id' , $c_id);
		$this->session->set_confirm( $this->db->update('content') );
		switch($c_status) {
			case 'Active'	: $action = 'Restore'; break;
			case 'Deleted'	: $action = 'Delete'; break;
			default			: break;
		}
		$this->mod_content->_log($c_id, $action );
		redirect($this->session->get_bread('list') );
	}

	function _set_rules() {
		$this->form_validation->set_rules('c_title', 'Title', 'trim|required|xss_clean');
		$this->form_validation->set_rules('c_content_full', 'Content', 'trim');
		$this->form_validation->set_rules('c_content_intro', 'Content Intro', 'trim');
		$this->form_validation->set_rules('c_publish_date', 'Publish Date', 'trim');
		$this->form_validation->set_rules('c_parent_id', 'Parent ID', 'trim');
	}

	function _set_db() {
		$this->db->set('c_title' , $this->input->post('c_title'));
		$this->db->set('c_content_full' , $this->input->post('c_content_full') );
		$this->db->set('c_content_intro' , $this->input->post('c_content_intro') );
		$this->db->set('c_parent_id' , $this->input->post('c_parent_id') );
		$this->db->set('c_module' , $this->mod );
		$this->db->set('c_lastedit_id' , $this->userinfo['u_id']);
		$this->db->set('c_publish_status' , 'Published');
		$c_publish_date = $this->input->post('c_publish_date');
		$c_publish_date = $c_publish_date ? $c_publish_date : date('Y-m-d H:i:s');
		$this->db->set('c_publish_date' , $c_publish_date );
	}



	//function do_index() {
	//	$res = $this->db->get('content');
	//	$all = $res->result_array();
	//
	//	foreach( $all as $k=>$tmp) {
	//		print "INDEX ".$tmp['c_title']."<br>";
	//
	//		$this->db->where('i_fid' , $tmp['c_id']);
	//		$res = $this->db->get('content_index');
	//		$result = $res->row_array();
	//		if($result) {
	//			print "ALREADY INDEXED ! <br>";
	//		} else {
	//			$index = $tmp['c_title'];
	//			$index = trim($index);
	//			$index_arr = explode( ' ', $index);
	//			$weight = 1;
	//			foreach( $index_arr as $l=>$tmp2) {
	//				$this->db->set('i_index' , $tmp2);
	//				$this->db->set('i_weight' , $weight);
	//				$this->db->set('i_fid', $tmp['c_id']);
	//				$this->db->insert('content_index');
	//
	//				print "$tmp2 > indexed <br>";
	//			}
	//		}
	//	}
	//	$this->view_list();
	//}

	//function index($t_id = 0, $orderby = 'c_id' , $ascdesc = 'ASC' , $page_number = 0 , $searchkey = '') {
	//	$this->session->validate(array('OPERATOR'));
	//	$this->session->set_bread('list');
	//
	//	$this->sci->assign('t_id', $t_id);
	//
	//	// user_tag privelidge, if in tag, it's TRUE
	//	//if(!$this->session->check_tag("OPERATOR"))
	//	//$this->sci->assign('TAG_IN' , TRUE);
	//
	//	$this->sci->assign('menu_index', '1');
	//
	//	// Search
	//	$like = array();
	//	$key = "";
	//	if ($searchkey != '') {
	//		$key = safe_base64_decode($searchkey);
	//		$like['c_title'] = $key;
	//		$like['c_content'] = $key;
	//		$like['c_content_buffer'] = $key;
	//		$like['t_name'] = $key;
	//	}
	//	$this->db->or_like($like);
	//	$this->sci->assign('searchkey' , $key);
	//
	//	// Order By
	//	$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
	//	$this->sci->assign('orderby' , $orderby);
	//	$this->sci->assign('ascdesc' , $ascdesc);
	//	$this->db->order_by($orderbyconv , $ascdesc);
	//
	//	// Pagination
	//	$this->sci->assign('page_number' , $page_number);
	//
	//	// Get content database
	//	$this->db->join('content_tag', "content_tag.t_id = content.t_id", 'left');
	//	//$this->db->order_by("content.c_id" ,  "desc");
	//	$this->db->from('content');
	//	$this->db->where('c_status' , "Active");
	//	// only show this category
	//	if($t_id != 0) {
	//		$this->db->where('content.t_id', $t_id);
	//	}
	//	$this->db->stop_cache();
	//
	//	$res = $this->db->get();
	//
	//	//MY ADDING ^^
	//	$temp = $res->result_array();
	//	//foreach($temp as $k => $row) {
	//	//	if( $this->session->check_tag( $row['t_name'] ) ) {
	//	//		$temp[$k]['tag_edit_status'] = 'TRUE';
	//	//	}
	//	//}
	//	//var_dump($temp);
	//	$this->sci->assign('maindata' , $temp);
	//
	//	//get all tag
	//	$this->db->where('t_status' , 'Active');
	//	$this->db->where('t_parentid' , 0);
	//	$res = $this->db->get('content_tag');
	//	$tags = $res->result_array();
	//	foreach($tags as $k=>$tmp) {
	//		$this->db->where('t_status' , 'Active');
	//		$this->db->where('t_parentid' , $tmp['t_id']);
	//		$res = $this->db->get('content_tag');
	//		$tags[$k]['child'] = $res->result_array();
	//	}
	//	$this->sci->assign('tags' , $tags);
	//
	//	$this->sci->da( $this->mod_path . 'list.htm');
	//}

	//function view_list($t_id = 0, $orderby = 'c_id' , $ascdesc = 'ASC' , $page_number = 0 , $searchkey = 'any') {
	//	$this->session->validate(array('OPERATOR'));
	//	$this->session->set_bread('list');
	//
	//	$this->sci->assign('t_id', $t_id);
	//	$this->sci->assign('menu_index', '1');
	//
	//	// Search
	//	$like = array();
	//	$key = "";
	//	if ($searchkey != 'any' ) {
	//		$key = safe_base64_decode($searchkey);
	//		//$like['c_title'] = $key;
	//		//$like['c_content'] = $key;
	//		//$like['c_content_buffer'] = $key;
	//		$key = trim($key);
	//		$key_arr = explode(' ', $key);
	//		foreach($key_arr as $k=>$tmp) {
	//			$this->db->or_like('i_index', $tmp);
	//		}
	//		$res = $this->db->get('content_index');
	//		$index_result = $res->result_array();
	//		print_r($index_result);
	//		print $searchkey;
	//		print "asd";
	//	}
	//	//$this->db->or_like($like);
	//	$this->sci->assign('searchkey' , $key);
	//
	//	// Order By
	//	$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
	//	$this->sci->assign('orderby' , $orderby);
	//	$this->sci->assign('ascdesc' , $ascdesc);
	//	$this->db->order_by($orderbyconv , $ascdesc);
	//
	//	// Pagination
	//	$this->sci->assign('page_number' , $page_number);
	//
	//	// Get content database
	//	$this->db->join('content_tag', "content_tag.t_id = content.t_id", 'left');
	//	//$this->db->order_by("content.c_id" ,  "desc");
	//	$this->db->from('content');
	//	$this->db->where('c_status' , "Active");
	//	// only show this category
	//	if($t_id != 0) {
	//		$this->db->where('content.t_id', $t_id);
	//	}
	//	$this->db->stop_cache();
	//	$res = $this->db->get();
	//	$maindata = $res->result_array();
	//	$this->sci->assign('maindata' , $maindata);
	//	$this->sci->da( $this->mod_path . 'list.htm');
	//}



//	function view_list($t_id = 0, $orderby = 'c_id' , $ascdesc = 'ASC' , $page_number = 0 , $searchkey = 'any') {
//		$this->session->validate(array('OPERATOR'));
//		$this->session->set_bread('list');
//
//		$this->sci->assign('t_id', $t_id);
//		$this->sci->assign('menu_index', '1');
//
//		//Search: default values
//		$like = array();
//		$key = "";
//		$index_result = array();
//
//		if ($searchkey != 'any' ) {
//			$key = safe_base64_decode($searchkey);
//			$this->sci->assign('searchkey' , $key);
//		}
//
//		// Pagination
//		$this->sci->assign('page_number' , $page_number);
//
//		//Search: apply search filter
//		if ($searchkey != 'any' ) {
//			$key = safe_base64_decode($searchkey);
//			$key = trim($key);
//			$key_arr = explode(' ', $key);
//			foreach($key_arr as $k=>$tmp) {
//				$this->db->or_like('i_index', $tmp);
//			}
//			$this->db->join('content' , 'content.c_id = content_index.i_fid' , 'left');
//			$this->db->select('*, SUM(i_weight) as wgt');
//			$this->db->group_by('c_id');
//			//$this->db->order_by('wgt' , 'desc');
//			$this->db->from('content_index');
//		} else {
//			$this->db->from('content');
//		}
//
//		// Order By
//		$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
//		$this->sci->assign('orderby' , $orderby);
//		$this->sci->assign('ascdesc' , $ascdesc);
//		$this->db->order_by($orderbyconv , $ascdesc);
//
//		$this->db->join('content_tag', "content_tag.t_id = content.t_id", 'left');
//		$this->db->where('c_status' , "Active");
//		if($t_id != 0) {
//			$this->db->where('content.t_id', $t_id);
//		}
//
//		$this->db->stop_cache();
//		$res = $this->db->get();
//		$maindata = $res->result_array();
//		$this->sci->assign('maindata' , $maindata);
//		$this->sci->da( $this->mod_path . 'list.htm');
//	}
//
//
//	function delete($c_id = 0) {
//		$this->session->validate(array('ADMIN'));
//		// Delete data
//		$this->db->where('c_id' , $c_id);
//		$this->db->set('c_status' , 'Deleted');
//		$this->db->set('c_content_status' , 'Deleted');
//		$this->db->update('content');
//
//		redirect('admin/content/index');
//	}
//
//	function add($t_id = 0) {
//		$this->session->validate(array('OPERATOR'));
//		$this->session->set_bread('form');
//
//		// Validation class
//		$this->load->library('form_validation');
//		$this->_set_rules();
//
//		// Load content_tag
//		$this->db->order_by('t_parentid' , 'desc');
//		$res = $this->db->get('content_tag');
//
//		$temp = array();
//		foreach ($res->result() as $row) {
//			// My Adding^^ if this user has this tag, permit to Add Content_tag
//			if( $this->session->check_tag( $row->t_name ) ) {
//				if($row->t_parentid !=0){
//						// Add prefix if this tag has parent
//						$this->db->flush_cache();
//						$this->db->from('content_tag');
//						$this->db->where('t_id', $row->t_parentid);
//						$tmp_res = $this->db->get();
//						$tmp_result = $tmp_res->result_array();
//						$temp[$row->t_id] = '['.$tmp_result[0]['t_name'].'] '.$row->t_name;
//				}else{
//						//throw away if this tag has a child
//						$this->db->flush_cache();
//						$this->db->from('content_tag');
//						$this->db->where('t_parentid', $row->t_id);
//						$tmp_res = $this->db->get();
//						$tmp_result = $tmp_res->result_array();
//						if(!$tmp_result) {
//							$temp[$row->t_id] = $row->t_name;
//						}
//
//				}
//			}
//		}
//		//var_dump($res->result());
//		$this->sci->assign('content_tag' , $temp);
//
//		// Skipauth for OPERATOR -> initial value
//		if(!$this->session->check_role("ADMIN")){
//			$save_status = 'Pending';
//		}else {
//			$save_status = 'Published';
//		}
//
//		if ($this->form_validation->run() == FALSE) {
//			$this->sci->da('admin/content/add.htm');
//		}
//		else {
//			$this->db->set('u_id' , $user['u_id']);
//			$this->db->set('t_id' , $this->input->post('t_id'));
//			$this->db->set('c_title' , $this->input->post('c_title'));
//			$this->db->set('c_title2' , $this->input->post('c_title2'));
//			$this->db->set('c_title3' , $this->input->post('c_title3'));
//
//			/*Upload banner*/
//			$this->_upload_banner();
//
//			// Skipauth for ADMIN
//			if($this->session->check_role("ADMIN") && $this->input->post('save_status') != 'Draft'){
//				$this->db->set('c_content' , $this->input->post('c_content'));
//				$this->db->set('c_content2' , $this->input->post('c_content2'));
//				$this->db->set('c_content3' , $this->input->post('c_content3'));
//			}else {
//				$this->db->set('c_content_buffer' , $this->input->post('c_content'));
//				$this->db->set('c_content_buffer2' , $this->input->post('c_content2'));
//				$this->db->set('c_content_buffer3' , $this->input->post('c_content3'));
//			}
//			$this->db->set('c_content_status' , $this->input->post('save_status'));
//			$this->db->set('c_create' , 'NOW()', FALSE);
//			if( $this->db->insert('content') ) {
//				$this->session->set_confirm(1);
//			} else {
//				$this->session->set_confirm(0);
//			}
//			redirect( $this->session->get_bread('list') );
//		}
//	}
//
//	function edit($c_id = 0) {
//		$this->session->validate(array('OPERATOR'));
//		$this->session->set_bread('form');
//		// Load User Active
//		$user = $this->session->get_user_information();
//
//		// Get user information
//		$res = $this->db->
//			where('c_id' , $c_id)->
//			where('c_status' ,"Active")->
//			get('content');
//		if ($row = $res->row()) {
//			$this->sci->assign('maindata' , $row);
//			$maindata = $row;
//		}
//		else {
//			redirect('admin/content/index');
//		}
//
//		// Validation class
//		$this->load->library('validation');
//		$this->_set_rules();
//
//		// Load content_tag
//		$this->db->order_by('t_parentid' , 'desc');
//		$res = $this->db->get('content_tag');
//
//		$temp = array();
//		foreach ($res->result() as $row) {
//			// My Adding^^ if this user has this tag, permit to Add Content_tag
//			if( $this->session->check_tag( $row->t_name ) ) {
//				// Add prefix if this tag has parent
//				if($row->t_parentid !=0){
//						$this->db->flush_cache();
//						$this->db->from('content_tag');
//						$this->db->where('t_id', $row->t_parentid);
//						$tmp_res = $this->db->get();
//						$tmp_result = $tmp_res->result_array();
//					$temp[$row->t_id] = '['.$tmp_result[0]['t_name'].'] '.$row->t_name;
//				}else{
//					//throw away if this tag has a child
//						$this->db->flush_cache();
//						$this->db->from('content_tag');
//						$this->db->where('t_parentid', $row->t_id);
//						$tmp_res = $this->db->get();
//						$tmp_result = $tmp_res->result_array();
//						if(!$tmp_result) {
//							$temp[$row->t_id] = $row->t_name;
//						}
//				}
//			}
//		}
//		$this->sci->assign('content_tag' , $temp);
//
//
//		// Set default variable
//		$this->validation->t_id = $maindata->t_id;
//		$this->validation->c_title = $maindata->c_title;
//		$this->validation->c_title2 = $maindata->c_title2;
//		$this->validation->c_title3 = $maindata->c_title3;
//		$this->validation->c_content = $maindata->c_content;
//		$this->validation->c_content2 = $maindata->c_content2;
//		$this->validation->c_content3 = $maindata->c_content3;
//		$this->validation->c_small_image = $maindata->c_small_image;
//		$this->validation->c_big_image = $maindata->c_big_image;
//		$this->validation->c_mini_content = $maindata->c_mini_content;
//		$this->validation->c_banner = $maindata->c_banner;
//
//		// Skipauth for OPERATOR -> initial value
//		if(!$this->session->check_role("ADMIN")){
//			$this->validation->save_status = 'Pending';
//		}else {
//			$this->validation->save_status = 'Published';
//		}
//
//		if ($this->validation->run() == FALSE) {
//			$this->sci->assign('validation' , $this->validation);
//			$this->sci->da('admin/content/edit.htm');
//		}
//		else {
//			//save the source before edit in history
//			$this->db->set('u_id' , $user['u_id']);
//			$this->db->set('c_id' , $c_id);
//			if($maindata->c_content_status == 'Pending' || $maindata->c_content_status == 'Draft'){
//				//pending: usual Edit
//				$this->db->set('ch_content' , $maindata->c_content_buffer);
//				$this->db->set('ch_content2' , $maindata->c_content_buffer2);
//				$this->db->set('ch_content3' , $maindata->c_content_buffer3);
//			}
//			elseif($maindata->c_content_status == 'Published'){
//				//published: data has been showed to public
//				$this->db->set('ch_content' , $maindata->c_content);
//				$this->db->set('ch_content2' , $maindata->c_content2);
//				$this->db->set('ch_content3' , $maindata->c_content3);
//				$this->db->set('c_small_image' , $maindata->c_small_image);
//				$this->db->set('c_big_image' , $maindata->c_big_image);
//				$this->db->set('c_mini_content' , $maindata->c_mini_content);
//			}
//			//status for saving in history too
//			if($this->validation->save_status == 'Draft'){
//				$this->db->set('ch_content_type' , $this->validation->save_status);
//			}else{
//				$this->db->set('ch_content_type' , 'Finished');
//			}
//			$this->db->set('c_banner' , $maindata->c_banner);
//			$this->db->set('ch_time' , 'NOW()' , FALSE);
//			$this->db->insert('content_history');
//
//			if($this->input->post('delete_banner') == 'yes' ) {
//				unlink($this->input->post('default_banner'));
//				unlink($this->input->post('default_banner_thumb'));
//				$this->db->set('c_banner' , '');
//			}else {
//					/*Upload banner*/
//					$this->_upload_banner();
//			}
//
//			$this->db->set('u_id' , $user['u_id']);
//			$this->db->set('t_id' , $this->validation->t_id);
//			$this->db->set('c_title' , $this->validation->c_title);
//			$this->db->set('c_title2' , $this->validation->c_title2);
//			$this->db->set('c_title3' , $this->validation->c_title3);
//			// Skipauth for ADMIN
//			if($this->session->check_role("ADMIN") && $this->validation->save_status != 'Draft'){
//				$this->db->set('c_content' , $this->validation->c_content);
//				$this->db->set('c_content2' , $this->validation->c_content2);
//				$this->db->set('c_content3' , $this->validation->c_content3);
//				$this->db->set('c_small_image' , $this->validation->c_small_image);
//				$this->db->set('c_big_image' , $this->validation->c_big_image);
//				$this->db->set('c_mini_content' , $this->validation->c_mini_content);
//				$this->db->set('c_content_buffer' , '');
//				$this->db->set('c_content_buffer2' , '');
//				$this->db->set('c_content_buffer3' , '');
//			}else {
//				$this->db->set('c_content_buffer' , $this->validation->c_content);
//				$this->db->set('c_content_buffer2' , $this->validation->c_content2);
//				$this->db->set('c_content_buffer3' , $this->validation->c_content3);
//			}
//			$this->db->set('c_content_status' , $this->validation->save_status);
//			$this->db->set('c_time' , 'NOW()', FALSE);
//			$this->db->where('c_id' , $c_id);
//
//			if($this->db->update('content')) {
//				$this->session->set_confirm(1);
//			} else {
//                $this->session->set_confirm(0);
//			}
//			redirect( $this->session->get_bread('list') );
//		}
//	}
//
//	function view() {
//		$this->sci->d('admin/content/view.htm');
//	}
//
//	function _set_rules() {
//		$this->form_validation->set_rules('t_id', 'Tag ID', 'trim|strip_tags|required');
//		$this->form_validation->set_rules('c_title', 'Title', 'trim|strip_tags|required');
//		$this->form_validation->set_rules('c_title2', 'Title 2', 'trim|strip_tags');
//		$this->form_validation->set_rules('c_title3', 'Title 3', 'trim|strip_tags');
//		$this->form_validation->set_rules('c_content', 'Content', 'trim');
//		$this->form_validation->set_rules('c_content2', 'Content 2', 'trim');
//		$this->form_validation->set_rules('c_content3', 'Content 3', 'trim');
//		$this->form_validation->set_rules('c_banner', 'Content Banner', 'trim|strip_tags');
//		$this->form_validation->set_rules('save_status', 'Saev Status', 'trim');
//
//	}
//
//	function _upload_banner() {
//		if (!empty($_FILES)) {
//			if ($_FILES[ $this->upload_name ]['tmp_name']) {
//				$code = uniqid();
//				$realFile = $_FILES[ $this->upload_name ]['name'];
//				$extension = end(explode(".", $realFile));
//				$tempFile = $_FILES[ $this->upload_name ]['tmp_name'];
//				$filename = $code . '.' . $extension;
//				$targetFile =  $this->image_path . $filename;
//				$thumbFile =  $this->thumb_path . $filename;
//				move_uploaded_file($tempFile, $targetFile);
//				// Make thumbnail
//				$config['image_library'] = 'gd2';
//				$config['source_image'] = $targetFile;
//				$config['new_image'] = $thumbFile;
//				$config['width'] =  $this->thumb_width ;
//				$config['height'] =  $this->thumb_height ;
//				$config['maintain_ratio'] = FALSE;
//				$this->load->library('image_lib', $config);
//				$this->image_lib->resize();
//				$this->db->set('c_banner' , $filename);
//			}
//		}
//	}
//
//
//	function _find($c_id = 0){
//		$res = $this->db->
//			where('c_id' , $c_id)->
//			select('c_content_buffer')->
//			get('content');
//		$res = $res->row_array();
//
//		return $res['c_content_buffer'];
//	}
//
//
//	function journal($a = '', $b = '') {
//		if (is_numeric($a) && is_numeric($b)) {
//			// Month View
//		}
//		$prefs['template'] = '
//		   {table_open}<table width="150" border="0" cellpadding="0" cellspacing="0">{/table_open}
//
//		   {heading_row_start}<tr>{/heading_row_start}
//
//		   {heading_previous_cell}<th><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
//		   {heading_title_cell}<th colspan="{colspan}">{heading}</th>{/heading_title_cell}
//		   {heading_next_cell}<th><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
//
//		   {heading_row_end}</tr>{/heading_row_end}
//
//		   {week_row_start}<tr>{/week_row_start}
//		   {week_day_cell}<td>{week_day}</td>{/week_day_cell}
//		   {week_row_end}</tr>{/week_row_end}
//
//		   {cal_row_start}<tr>{/cal_row_start}
//		   {cal_cell_start}<td align="center">{/cal_cell_start}
//
//		   {cal_cell_content}<a href="{content}">{day}</a>{/cal_cell_content}
//		   {cal_cell_content_today}<div class="highlight"><a href="{content}">{day}</a></div>{/cal_cell_content_today}
//
//		   {cal_cell_no_content}{day}{/cal_cell_no_content}
//		   {cal_cell_no_content_today}<div class="highlight">{day}</div>{/cal_cell_no_content_today}
//
//		   {cal_cell_blank}&nbsp;{/cal_cell_blank}
//
//		   {cal_cell_end}</td>{/cal_cell_end}
//		   {cal_row_end}</tr>{/cal_row_end}
//
//		   {table_close}</table>{/table_close}
//		';
//		$this->load->library('calendar', $prefs);
//
//		$data = array(
//		               3  => '/news/article/2006/03/',
//		               7  => '/news/article/2006/07/',
//		               13 => '/news/article/2006/13/',
//		               26 => '/news/article/2006/26/'
//		             );
//		$this->sci->assign('calendar', $this->calendar->generate(2008, 7, $data));
//		$this->sci->da('admin/content/journal.htm');
//	}

}
