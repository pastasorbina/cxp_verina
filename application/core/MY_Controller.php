<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * MY_Controller
 *
 * @property CI_DB_active_record $db
 */

class MY_Controller extends CI_Controller {
	var $mod_path = '';
	var $mod_url = '';
	var $mod = '';
	var $mod_title = '';
	var $mod_subtitle = '';
	var $userinfo = array();

	/* defaults */
	var $default_pagelimit = 40;

	/* default image directory & dimensions */
	var $image_directory = './userfiles/upload/';
	var $thumb_directory = './userfiles/upload/thumb/';
	var $thumb_width = 125;
	var $thumb_height = 125;
	var $maintain_ratio = FALSE;

	/* default template */
	var $template_index = "index.htm";
	var $template_add = "edit.htm";
	var $template_edit = "edit.htm";

	/* table schema */
	var $table_name = '';
	var $id_field = '';
	var $status_field = '';
	var $entry_field = '';
	var $stamp_field = '';
	var $deletion_field = '';
	var $order_field = '';
	var $order_dir = 'DESC';
	var $label_field = '';

	var $author_field = '';
	var $editor_field = '';

	var $search_in = array();

	var $suffix_param = array();

	private $room;

	function __construct() {
		parent::__construct();

		$this->_setup_ckeditor();

		$this->branch_id = $this->branch->get_branch_id();

		$this->session->get_confirm();
		$this->sci->assign('LAST_LIST', $this->session->get_bread('list'));
		$this->_load_config();


		//assign configs
		$this->db->where('b_id' , $this->branch_id);
		$res = $this->db->get('config');
		$temp = $res->result_array();
		$site_config = array();
		foreach($temp as $k=>$tmp) {
			$site_config[$tmp['c_key']] = $tmp['c_value'];
		}
		$this->site_config = $site_config;
		$this->sci->assign('site_config' , $site_config);

		//get room from sci library
		$this->room_path = $this->sci->get_room_path();

		//setup ckeditor path
		$this->_setup_ckeditor();

		$this->sci->assign('current_url' , $this->uri->uri_string());
	}


	function _init() {

		$room = $this->sci->get_room();

		//get and assign current module from controller's class name
		$this->mod = strtolower(get_class($this));
		$this->sci->assign('mod', $this->mod);

		//get and assign current module url
		if($room == 'main') {
			$this->mod_url = site_url().$this->mod.'/';
		} else {
			$this->mod_url = site_url().$this->sci->get_room_path().$this->mod.'/';
		}
		$this->sci->assign('mod_url', $this->mod_url);

		//get and assign current module path
		$this->mod_path = $this->sci->get_room_path().$this->mod.'/';
		$this->sci->assign('mod_path', $this->mod_path);

		//set current module to SCI library
		$this->sci->set_module($this->mod);

		//assign current module title
		$this->sci->assign('mod_title', $this->mod_title);
		$mod_title_html = ''.$this->mod_title.'';
		if( $this->mod_title != '') {
			$this->sci->assign('mod_title_html', $mod_title_html);
		}

		//assign current pagelimit
		$this->sci->assign('default_pagelimit', $this->default_pagelimit);

		//if($this->sci->get_display_type() != 'plain') {
			//$this->output->enable_profiler($this->config->item('debug_mode'));
		//}
		//print $this->sci->get_display_type();
		//DO NOT RETREIVE USER INFO HERE ! member will logged automatically if you do

	}

	function _load_sidebar($flag = TRUE) {

		//sidebar, get all other content label excet this
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_status' , 'Active');
		$this->db->where('cl_type' , 'article');
		$res = $this->db->get('content_label');
		$other_cl = $res->result_array();

		foreach($other_cl as $k=>$tmp) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('content.cl_id' , $tmp['cl_id']);
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->where('c_publish_status' , 'Published');
			$this->db->limit(4, 0);
			$this->db->order_by('c_date' , 'DESC');
			$res = $this->db->get('content');
			$data = $res->result_array();
			foreach($data as $k2=>$tmp2) {
				$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
				$this->db->where('mr_foreign_id' , $data[$k2]['c_id']);
				$this->db->where('mr_module' , 'content');
				$res = $this->db->get('media_relation');
				$media = $res->result_array();
				foreach($media as $l=>$tmp3) {
					$pos = $tmp3['mr_pos'];
					$data[$k2]['media'][$pos] = $tmp3;
				}
			}
			$other_cl[$k]['entries'] = $data;
		}
		$this->sci->assign('other_cl' , $other_cl);


		$sidebar_template = 'sidebar.htm';
		$mod_sidebar = $this->sci->fetch('main/sidebar.htm');
		$this->sci->assign('mod_sidebar', $mod_sidebar);

		//if($flag == TRUE){
		//	$filename = dirname(dirname(__FILE__)).'/views/main/'.$sidebar_template;
		//		if(read_file($filename)){
		//			$mod_sidebar = $this->sci->fetch($sidebar_template);
		//			$this->sci->assign('mod_sidebar', $mod_sidebar);
		//		}else{
		//
		//		}
		//}
	}

	function _load_sponsor($flag = TRUE) {
		//$this->db->where('spn_status' , 'Active');
		//$res = $this->db->get('sponsor');
		//$sponsor = $res->result_array();
		//$this->sci->assign('snip_sponsor' , $sponsor);
	}


	function _load_config() {
		$this->config->load('security');
		$this->output->enable_profiler($this->config->item('enable_profiler'));
	}

	function _get_mod() {
		return $this->mod;
	}


	function _setup_ckeditor() {
		@session_start();
		$_SESSION['userfiles_baseurl'] = site_url().$this->config->item('userfiles_path').$this->config->item('ckeditor_path');
		$_SESSION['userfiles_basedir'] = $this->config->item('absolute_path').$this->config->item('userfiles_path').$this->config->item('ckeditor_path');

		//print $_SESSION['userfiles_baseurl']."<br>";
		//print $_SESSION['userfiles_basedir'];
	}


	function search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$offset = 0;
		$ascdesc = $this->input->post('ascdesc');
		$encodedkey = safe_base64_encode($searchkey);
		if( !$encodedkey ) { $encodedkey = ''; }
		redirect("$page$pagelimit/$offset/$orderby/$ascdesc/$encodedkey");
	}

	// @deprecated
	function do_search( ) {
		$page = $this->input->post('page');
		$searchkey = $this->input->post('searchkey');
		$searchby = $this->input->post('searchby');
		$pagelimit = $this->input->post('pagelimit');
		$orderby = $this->input->post('orderby');
		$offset = $this->input->post('offset');
		$ascdesc = $this->input->post('ascdesc');
		$searchkey = safe_base64_encode($searchkey);
		if( !$searchkey ) { $searchkey = ''; }
		redirect("$page$offset/$pagelimit/$orderby/$ascdesc/$searchby/$searchkey");
	}

	// @deprecated
	function do_delete( $id=0 ) {
		$this->db->where($this->id_field , $id);
		$this->db->set($this->status_field , 'Deleted');
		if( $this->db->update( $this->table_name ) ) {
			$this->session->set_confirm(1);
		} else {
			$this->session->set_confirm(0);
		}
		redirect( $this->session->get_bread('list') );
	}

	// @deprecated
	function do_suspend( $id=0 ) {
		$this->db->where($this->id_field , $id);
		$this->db->set($this->status_field , 'SUSPENDED');
		if($this->db->update( $this->table_name )) {
			$this->session->set_confirm(1);
		} else {
			$this->session->set_confirm(0);
		}
		redirect( $this->session->get_bread('list') );
	}

	function do_batch_status( ) {
		$status = $this->input->post('status');
		$entry_check = $this->input->post('entry_check');

		if( !isset($_POST['entry_check']) ) {
			$this->session->set_confirm(0, 'nothing selected');
		} else {
			$this->db->where_in( $this->id_field , $entry_check);
			$this->db->set( $this->status_field , $status);

			 if($this->db->update( $this->table_name )) {
				$this->session->set_confirm(1);
			} else {
				$this->session->set_confirm(0);
			}
		}
		redirect( $this->session->get_bread('list') );
	}



	/**
	 * upload image
	 * @access private
	 *
	 */
	function _upload_image( $fieldname = '', $crop = FALSE ) {
		$filename = FALSE;
		if (!empty($_FILES)) {
			if ($_FILES[$fieldname]['tmp_name']) {
				$code = uniqid();
				$realFile = $_FILES[$fieldname]['name'];
				$extension = end(explode(".", $realFile));
				$tempFile = $_FILES[$fieldname]['tmp_name'];
				$filename = $code . '.' . $extension;
				$targetFile =  $this->image_directory . $filename;
				$thumbFile =  $this->thumb_directory . $filename;
				$thumbFile2 =  $this->thumb_directory . 't_'.$filename;
				move_uploaded_file($tempFile, $targetFile);

				if($crop == TRUE) {
					$size = getimagesize($thumbFile);
					$width = $size[0];
					$height = $size[1];
					if($width > $this->thumb_width) {
						$config['width'] = $this->thumb_width;
					}
					if($height > $this->thumb_height) {
						$config['height'] = $this->thumb_height;
					}
					$config['image_library'] = 'gd2';
					$config['source_image'] = $targetFile;
					$config['new_image'] = $thumbFile;
					$config['maintain_ratio'] = TRUE;
					$this->load->library('image_lib');

					$this->image_lib->initialize($config);
					$this->image_lib->resize();
					$this->image_lib->clear();

					$size = getimagesize($thumbFile);
					$width = $size[0];
					$height = $size[1];

					$left_offset = 0;
					$top_offset = 0;
					if($width > $this->thumb_width) {
						$left_offset = ($width / 2) - ($this->thumb_width / 2);
					}
					if($height > $this->thumb_height) {
						$top_offset = ($height / 2) - ($this->thumb_height / 2);
					}
					$config['image_library'] = 'gd2';
					$config['source_image'] = $thumbFile;
					$config['new_image'] = $thumbFile;
					$config['width'] = $this->thumb_width;
					$config['height'] = $this->thumb_height;
					$config['x_axis'] = $left_offset;
					$config['y_axis'] = $top_offset;
					$config['maintain_ratio'] = FALSE;
					$this->load->library('image_lib');
					$this->image_lib->initialize($config);
					$this->image_lib->crop();
				} else {
					// Make thumbnail
					$config['image_library'] = 'gd2';
					$config['source_image'] = $targetFile;
					$config['new_image'] = $thumbFile;
					$config['width'] = $this->thumb_width;
					$config['height'] = $this->thumb_height;
					$config['maintain_ratio'] = $this->maintain_ratio;
					$this->load->library('image_lib');

					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}

				$this->image_lib->clear();
			}
		}
		return $filename;
	}



	/**
	 * insert media
	 * @access private
	 *
	 */
	function _insert_media( $id=0, $action='update' ) {
		//insert/update media
		$m_id = $this->input->post('m_id');
		$mr_id = $this->input->post('mr_id');
		$mr_pos = $this->input->post('mr_pos');
		//TODO:insert validation here

		foreach($mr_pos as $k=>$tmp) {
			//get current media related to this item
			$this->db->where('mr_id' , $mr_id[$k]);
			$this->db->where('mr_pos' , $mr_pos[$k] );
			$this->db->where('mr_module' , $this->mod );
			$this->db->where('mr_status' , 'Active');
			$this->db->where('b_id' , $this->branch_id);
			$this->db->order_by('mr_stamp' , 'DESC');
			$res = $this->db->get('media_relation');
			$result = $res->row_array();

			$this->db->set('m_id' , $m_id[$k] );
			$this->db->set('mr_pos' , $mr_pos[$k] );
			$this->db->set('mr_foreign_id' , $id );
			$this->db->set('mr_module' , $this->mod );
			$this->db->set('b_id' , $this->branch_id);
			if( $result ) {
				$this->db->where('mr_id' , $mr_id[$k] );
				$this->db->update('media_relation');
			} else {
				$this->db->set('mr_entry' , date('Y-m-d H:i:s') );
				$this->db->insert('media_relation');
			}
		}
	}

	function _get_media( $position = array(), $id=0 ) {
		foreach($position as $k=>$tmp) {
			$this->db->join('media' , 'media_relation.m_id = media.m_id' , 'left');
			$this->db->where('mr_status' , 'Active' );
			$this->db->where('mr_foreign_id' , $id);
			$this->db->where('mr_module' , $this->mod);
			$this->db->where('mr_pos' , $tmp['pos']);
			$res = $this->db->get('media_relation');
			$result = $res->row_array();
			$position[$k]['data'] = $result;
		}
		return $position;
	}






	/**
	 * Set Form Validation Rules
	 * @access private
	 */
	function _set_rules(){
	}

	/**
	 * Set DB
	 * @access private
	 */
	function _set_db(){
	}


	/**
	 * Change Status
	 * @access public
	 */
	function delete($id=0) {
		$this->change_status($id, 'Deleted');
	}

	function suspend($id=0) {
		$this->change_status($id, 'Suspended');
	}

	function activate($id=0) {
		$this->change_status($id, 'Active');
	}

	function change_status($id=0, $status="Active") {
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$result = $res->row_array();
		if(!$result) {
			$this->session->set_confirm(0, 'entry not found !' );
			redirect($this->session->get_bread('list') );
		}

		$this->db->set($this->status_field , $status);
		if($status == 'Deleted') {
			$this->db->set($this->deletion_field , date('Y-m-d H:i:s') );
		}
		$this->db->where($this->id_field , $id);
		if(!$this->db->update($this->table_name)){
			$this->session->set_confirm(0);
		} else {
			$this->session->set_confirm(1);
		}

		if($status) {
			switch($status) {
				case 'Active'	: $action = 'activate'; break;
				case 'Deleted'	: $action = 'delete'; break;
				default			: break;
			}
		}
		redirect($this->session->get_bread('list') );
	}



	/**
	 * Export
	 * @access public
	 */
	function export_select() {
		$this->select('ec_name AS Name');
	}

	function export_excel() {
		$this->load->dbutil();

		$this->export_select();
		$this->join_setting();

		$res = $this->db->
			where($this->status_field , 'Active')->
			get($this->table_name);

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"{$this->table_name}.csv\"");

		echo $this->dbutil->csv_from_result($res);
	}


	/**
	 * Setting
	 * @access public
	 *
	 */
	function validation_setting($action='add') {
		$this->load->library('form_validation');
	}

	function enum_setting($maindata=array()) { return $maindata; }
	function iteration_setting($maindata=array()) { return $maindata; }

	function paging_setting() {
		$paging = array();
		$paging[10] = 10;
		$paging[20] = 20;
		$paging[50] = 50;
		$paging[100] = 100;
		$paging[500] = 500;
		//$this->sci->assign('paging' , $paging);
	}

	function join_setting() { }
	function database_setter($action='add') { }
	function select_setting() {
		$this->db->select('*');
	}

	function where_setting() {
		$this->db->where($this->status_field ,"Active");
	}

	function search_setting($key = '') {
	}

	function post_add_handle() { }
	function post_edit_handle() { }
	function post_delete_handle() { }

	function pre_add() {}
	function pre_delete($id=0) {}
	function pre_edit($id=0) {}
	function pre_add_edit() {}

	function post_add($insert_id=0) {}
	function post_delete($id=0) {}
	function post_edit($id=0) {}


	/**
	 * Index
	 * @access public
	 *
	 */
	function index($pagelimit='', $offset=0, $orderby='', $ascdesc='', $encodedkey='') {
		$this->session->set_bread('list');
		$this->pre_index();
		$this->determine_action();
		if ($orderby == '') $orderby = $this->order_field;
		if ($orderby == '') $orderby = $this->id_field;
		if ($ascdesc == '') $ascdesc = $this->order_dir;
		if ($pagelimit == '') $pagelimit = $this->default_pagelimit;
		$this->sci->assign('pagelimit' , $pagelimit);
		$this->sci->assign('offset' , $offset);
		$this->sci->assign('orderby' , $orderby);
		$this->sci->assign('ascdesc' , $ascdesc);
		/*--cache-start--*/
		$this->db->start_cache();
			if($encodedkey != ''){ $this->pre_search($encodedkey); }
			$orderbyconv = preg_replace('/_DOT_/' , '.', $orderby);
			$this->db->order_by($orderbyconv , $ascdesc);
			$this->db->from($this->table_name);
			$this->join_setting();
			$this->where_setting();
			$this->select_setting();
		$this->db->stop_cache();
		/*--cache-stop--*/

		// Pagination
		$total = $this->db->count_all_results($this->table_name);
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."index/". $pagelimit ."/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 5;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get($this->table_name);
		$this->db->flush_cache();
		$maindata = $res->result_array();
		$maindata = $this->append_user($maindata);
		$maindata = $this->enum_setting($maindata);
		$maindata = $this->iteration_setting($maindata);
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );
		$this->post_index();
		$this->sci->da('index.htm');
	}

	function pre_search($encodedkey) {
		$searchkey = safe_base64_decode($encodedkey);
		if(!empty($this->search_in)) {
			foreach($this->search_in as $k=>$tmp) { $this->db->or_like($tmp, $searchkey); }
		} else {
			$this->search_setting($searchkey);
		}
		$this->sci->assign('searchkey' , $searchkey);
	}

	/**
	 * Post Index
	 * @access public
	 *
	 */
	function post_index(){
	}

	/**
	 * Pre Index
	 * @access public
	 *
	 */
	function pre_index(){
		$this->sci->assign('thumb_directory' , $this->thumb_directory);
	}

	/**
	 * Determine Action
	 * @access public
	 *
	 */
	function determine_action() {
		$action = $this->input->post('action');
		switch($action) {
			case 'add' 		: $this->add(); return;
			case 'delete' 	: $this->delete(); return;
			case 'edit' 	: $this->edit(); return;
		}
	}

	/**
	 * Append User
	 * @access public
	 *
	 */
	function append_user($maindata = array()) {
		foreach($maindata as $k=>$tmp) {
			if($this->author_field != '') {
				$this->db->where('u_id' , $tmp[$this->author_field] );
				$res = $this->db->get('user');
				$author = $res->row_array();
				$maindata[$k]['author'] = $author;
			}
			if($this->editor_field != '') {
				$this->db->where('u_id' , $tmp[$this->editor_field] );
				$res = $this->db->get('user');
				$editor = $res->row_array();
				$maindata[$k]['editor'] = $editor;
			}
		}
		return $maindata;
	}

	/**
	 * List Select
	 * @access public
	 *
	 */
	function ajax_list_select($pagenum = 1 , $pagelimit = '20', $orderby='', $ascdesc='DESC', $encodedkey='') {
		$this->sci->assign('_post' , $_POST);
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

	/**
	 * Add
	 * @access public
	 *
	 */
	function add() {
		$this->sci->assign('ajax_action' , 'add');
		$this->pre_add_edit();
		$this->pre_add();

		$this->load->library('form_validation');
		$this->validation_setting('add');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da($this->template_add);
		}else{
			$this->database_setter('add');
			$this->db->set($this->entry_field , 'NOW()', FALSE );
			$ok = $this->db->insert($this->table_name);
			$insert_id = $this->db->insert_id();
			$this->post_add($insert_id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url.'add');
			} else {
				$this->session->set_confirm(1);
				redirect($this->session->get_bread('list') );
			}
		}
	}

	/**
	 * Edit
	 * @access public
	 *
	 */
	function edit( $id=0 ) {
		$this->sci->assign('ajax_action' , 'edit');
		$this->pre_add_edit();
		$this->join_setting();
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->pre_edit($id);

		$this->load->library('form_validation');
		$this->validation_setting('edit');
		if($this->form_validation->run() == FALSE ) {
			$this->sci->da($this->template_edit);
		}else{
			$this->database_setter('edit');
			$this->db->where($this->id_field , $id);
			$ok = $this->db->update($this->table_name);
			$this->post_edit($id);
			if(!$ok) {
				$this->session->set_confirm(0);
				redirect($this->mod_url."edit/$id");
			} else {
				$this->session->set_confirm(1);
				redirect($this->session->get_bread('list') );
			}
		}
	}

	/**
	 * Ajax Edit
	 * @access public
	 *
	 */
	function ajax_edit($id=0) {
		$this->ajax_add_edit('edit', $id);
	}

	function ajax_add() {
		$this->ajax_add_edit('add');
	}

	function ajax_add_edit( $ajax_action='add', $id=0 ) {
		$this->sci->assign('use_ajax' , TRUE);
		$this->load->library('form_validation');

		$this->pre_add_edit();
		if($ajax_action == 'add') {
			$this->pre_add();
		} elseif($ajax_action == 'edit') {
			$this->pre_edit($id);
		}

		$this->join_setting();
		$this->db->where($this->id_field , $id);
		$res = $this->db->get($this->table_name);
		$data = $res->row_array();
		$this->sci->assign('data' , $data);
		$this->sci->assign('ajax_action' , $ajax_action);
		//@todo : validate here

		$this->sci->d($this->template_edit);
	}

	function ajax_submit_action( $id=0 ) {
		$this->sci->assign('use_ajax' , TRUE);
		$current_id = $this->input->post('current_id');
		$ajax_action = $this->input->post('ajax_action');

		$userinfo = $this->session->get_userinfo();

		$this->pre_add_edit();
		if($ajax_action == 'add') {
			$this->pre_add();
		} elseif($ajax_action == 'edit') {
			$this->pre_edit($current_id);
		}

		$this->load->library('form_validation');
		$this->validation_setting($ajax_action);
		if($this->form_validation->run() == FALSE ) {
			$ret['status'] = 'error';
			$ret['msg'] = strip_tags(validation_errors());
		} else {
			$this->database_setter($ajax_action);
			if($ajax_action == 'add') {
				if($this->author_field != '') {
					$this->db->set($this->author_field, $userinfo['u_id']);
				}
				if($this->entry_field != '') {
					$this->db->set($this->entry_field , date('Y-m-d H:i:s') );
				}
				$ok = $this->db->insert($this->table_name);
				$insert_id = $this->db->insert_id();
				$this->post_add($insert_id);
			} elseif($ajax_action == 'edit') {
				if($this->editor_field != '') {
					$this->db->set($this->editor_field, $userinfo['u_id']);
				}
				$this->db->where($this->id_field , $current_id);
				$ok = $this->db->update($this->table_name);
				$this->post_edit($current_id);
			}

			if(!$ok) {
				$ret['status'] = 'error';
				$ret['msg'] = 'cannot update database';
			} else {
				$this->session->set_confirm(1);
				$ret['status'] = 'ok';
			}
		}
		print json_encode($ret);

	}




	////////CAPTCHA

	function _generate_captcha() {
        //create random string
        $num_of_digit = 4;
        $n = rand(10e16, 10e22);
        $captcha_string = base_convert($n, 10, 31);
        $captcha_string = substr( $captcha_string, 0, $num_of_digit);
		//print $captcha_string;
        $vals = array(
            //'word' => $captcha_string,
            'word' => $captcha_string,
            'img_path' => './captcha/',
            'img_url' => site_url() . 'captcha/',
            //'font_path' => site_url().'fonts/texb.ttf',
            'font_path' => '/fonts/texb.ttf',
            'img_width' => 150,
            'img_height' => 40,
            'expiration' => 2
        );
        $cap = create_captcha($vals);

        $this->sci->assign('captcha_image', $cap['image']);
		//print($cap['image']);
		//print 's';
        //exit($cap["word"]);
        $this->session->set_userdata('captcha_string',$cap['word']);
        $this->session->set_userdata('captcha', $cap['word']);
    }

    function _checkcaptcha($str) {
        $val = $this->session->userdata('captcha_string');
        //$str = strtoupper($str);

        if (!$str || $str != $val) {
            $this->form_validation->set_message('_checkcaptcha', 'captcha invalid, try again');
            return FALSE;
        } else {
            return TRUE;
        }
    }



}
