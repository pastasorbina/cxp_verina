<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MY_SCI extends SCI {

	protected $CI;

	public $config = array();
	public $head = '';
	public $mod = '';

	public $branch = array();
	public $view_path = '';
	public $view_folder = '';
	public $display_type = 'default';

	private $room = 'main';
	private $room_path = 'main/';

	public $template_header = 'header.htm';
	public $template_footer = 'footer.htm';

	var $postlaunch = TRUE;

	public function __construct() {
		parent::__construct();
		$this->CI =& get_instance();

		$this->CI->benchmark->mark('sci_start');

		$this->assign('site_url', site_url());

		//get and assign assets, js, and css paths
		$this->assign('js_path', $this->CI->config->item('js_path'));
		$this->assign('css_path',  $this->CI->config->item('css_path'));
		$this->assign('lib_path' , $this->CI->config->item('lib_path'));
		$this->assign('remote_asset_url', $this->CI->config->item('remote_asset_url'));
		$this->assign('remote_url', $this->CI->config->item('remote_url'));

		//$this->init();
		$this->_assign_config();
		$this->_assign_misc();

		//assign current uri string
		$current_uri = site_url() . $this->CI->uri->uri_string();
		$this->assign('current_uri' , $current_uri);
		//$this->CI->load->library('session');
		//$this->CI->session->set_userdata('last_url', $current_uri);

		$filename = './postlaunch';
		if (file_exists($filename)) { $this->postlaunch = TRUE; } else { $this->postlaunch = FALSE; }

	}

	public function _run_profiler(){
		$this->output->enable_profiler(TRUE);
	}



	private function _assign_config() {
		$this->CI->db->where('c_type' , 'config');
		$res = $this->CI->db->get('config');
		$config = $res->result_array();
		$this->assign('site_config', $config);
	}

	public function load_sidebar(){
		//set sidebar
		$mainsidebar = $this->fetch('sidebar.htm');
		$this->assign('mainsidebar' , $mainsidebar);
	}

	private function _assign_for_main() {
		// get product type, add assign as main menu
		$this->CI->db->where('pt_status' , 'Active');
		$res = $this->CI->db->get('product_type');
		$main_menu = $res->result_array();
		$this->assign('main_menu' , $main_menu);

		$this->CI->db->where('pc_status' , 'Active');
		$res = $this->CI->db->get('product_category');
		$sb_product_category = $res->result_array();
		$this->assign('sb_product_category' , $sb_product_category);

		$this->CI->db->join('menu_position mp' , 'mp.mp_id = menu.mp_id' , 'left');
		$this->CI->db->where('m_status' , 'Active');
		$this->CI->db->where('mp_slug' , 'top_navi');
		$res = $this->CI->db->get('menu');
		$top_navi = $res->result_array();
		$this->assign('top_navi' , $top_navi);

		$this->CI->db->join('menu_position mp' , 'mp.mp_id = menu.mp_id' , 'left');
		$this->CI->db->where('m_status' , 'Active');
		$this->CI->db->where('mp_slug' , 'footer_column_1');
		$res = $this->CI->db->get('menu');
		$footer_column_1 = $res->result_array();
		$this->assign('footer_column_1' , $footer_column_1);

		$this->CI->db->join('menu_position mp' , 'mp.mp_id = menu.mp_id' , 'left');
		$this->CI->db->where('m_status' , 'Active');
		$this->CI->db->where('mp_slug' , 'footer_column_2');
		$res = $this->CI->db->get('menu');
		$footer_column_2 = $res->result_array();
		$this->assign('footer_column_2' , $footer_column_2);

		$this->CI->db->join('menu_position mp' , 'mp.mp_id = menu.mp_id' , 'left');
		$this->CI->db->where('m_status' , 'Active');
		$this->CI->db->where('mp_slug' , 'footer_column_3');
		$res = $this->CI->db->get('menu');
		$footer_column_3 = $res->result_array();
		$this->assign('footer_column_3' , $footer_column_3);




	}

	private function _assign_for_admin() {
		//get content labels
		$this->CI->db->where('cl_status' , 'Active');
		$this->CI->db->where('b_id' , $this->CI->branch->get_branch_id() );
		$res = $this->CI->db->get('content_label');
		$sidebar_cl = $res->result_array();
		$this->assign('sidebar_cl' , $sidebar_cl);
	}

	private function _assign_misc() {

	}

	private function _assign_menu(){
		$this->CI->db->join('menu_position' , 'menu_position.mp_id = menu.mp_id' , 'left');
		$this->CI->db->where('menu_position.mp_id' , 6);
		$this->CI->db->where('m_status' , "Active");
		$this->CI->db->where('menu.b_id' , $this->branch_id);
		$this->CI->db->where('m_parent_id' , '0');
		$this->CI->db->order_by('m_id' , 'asc');
		$res = $this->CI->db->get('menu');
		$top_menu = $res->result_array();
		foreach($top_menu as $k=>$tmp) {
			$this->CI->db->where('m_parent_id' , $tmp['m_id']);
			$this->CI->db->where('m_status' , 'Active');
			$this->CI->db->where('menu.b_id' , $this->branch_id);
			$res = $this->CI->db->get('menu');
			$top_menu[$k]['child'] = $res->result_array();
		}
		$this->assign('top_menu' , $top_menu);
	}

	private function load_additional_info() {
		if ($this->CI->session->userdata('u_id') != FALSE) {
			$this->assign('LOGGED_IN' , TRUE);
		}
		$this->assign_confirm();
	}

	private function _assign_online_visitors() {
		$sql = " SELECT COUNT(*) AS total FROM ci_sessions WHERE last_activity > (UNIX_TIMESTAMP() - 60 * 15) ";
		$res = $this->CI->db->query($sql);
		$online_now = $res->row()->total;
		$this->assign('ONLINE_VISITOR' , $online_now);
	}

	private function _assign_branch(){
		$this->CI->load->library('branch');
		$this->branch = $this->CI->branch->get_branch();
		$this->assign('_branch' , $this->branch);
	}

	private function _assign_server_time() {
		$server_time['year'] = date('Y');
		$server_time['month'] = date('m');
		$server_time['day'] = date('d');
		$server_time['hour'] = date('H');
		$server_time['minute'] = date('i');
		$server_time['second'] = date('s');
		$this->assign('SERVER_TIME' , $server_time);
	}





	//room setter
	public function set_postlaunch( $true=FALSE ) {
		$this->postlaunch = $true;
	}

	//room setter
	public function set_room( $room = 'main' ) {
		$this->CI->config->set_item('room', $room);
		$this->CI->config->set_item('room_path', $room."/" );
	}

	//init, alias for set_room TODO: changed to set room
	public function init( $room = 'main' ){
		$this->set_room($room);
	}

	//room getter
	public function get_room() {
		return $this->CI->config->item('room');
	}

	//room_path getter
	public function get_room_path() {
		return $this->CI->config->item('room_path');
	}

	//module setter
	public function set_module($mod_name) {
		$this->mod = $mod_name;
	}

	//module getter
	public function get_module() {
		return $this->mod;
	}

	public function get_view_folder() {
		return $this->view_folder;
	}

	public function set_view_folder($view_folder=''){
		$this->view_folder = $view_folder;
	}

	public function set_display_type($display_type=''){ $this->display_type = $display_type; }
	public function get_display_type(){ return $this->display_type; }

	//pre display
	public function pre_display(){
		//mark SCI end, before displaying view
		$this->CI->benchmark->mark('sci_end');
		$elapsed = $this->CI->benchmark->elapsed_time('sci_start', 'sci_end');
		$this->assign('elapsed', $elapsed );

		$room = $this->get_room();
		if($room == 'admin') {
			$this->_assign_for_admin();
		} else {
			$this->_assign_for_main();
		}

		if($this->postlaunch == TRUE && $room != "admin") {
			redirect(site_url()."comingsoon");
		}

	}


	public function find_template($template='') {
		$segment_arr = $this->CI->uri->segment_array();
		$path_arr = array();
		$path = '';
		$full_path = '';
		$ok = FALSE;
		if(sizeof($segment_arr) == 0 || $this->view_folder != ''){
			return $this->view_folder."index.htm";
		} else {
			$segment_temp = $segment_arr;
			foreach($segment_temp as $k=>$tmp) {
				$path = implode('/', $segment_temp);
				$file = APPPATH."views/".$path."/".$template;
				if (is_readable($file) ) {
					$full_path = $file; $ok = TRUE; break;
				} else {
					$ok = FALSE;
				}
				array_pop($segment_temp);
			}
		}
		if($ok == FALSE) {
			return $ok;
		} else {
			return $full_path;
		}
	}

	public function da( $template='', $noauto=FALSE ) {
		$this->pre_display();
		$this->set_display_type('default');


		$param1 = $this->CI->uri->segment(1);
		if (is_readable(APPPATH."views/$param1/header.htm") && $param1 != "" ) {
			$this->display("$param1/header.htm");
		} else {
			$this->display("header.htm");
		}

		switch($noauto) {
			case TRUE :
				$this->display($template); break;
			case FALSE :
				if( $template = $this->find_template($template) ) {
					$this->display($template);
				} else {
					show_error(404);
				}
				break;
		}

		if (is_readable(APPPATH."views/$param1/header.htm") && $param1 != "" ) {
			$this->display("$param1/footer.htm");
		} else {
			$this->display("footer.htm");
		}
	}


	public function d($template='', $noauto=FALSE){
		$this->pre_display();
		$this->set_display_type('plain');

		if($noauto == FALSE) {
			$get_template = $this->find_template($template);
			if($get_template == FALSE) {
				echo 'Sorry, cannot find the page you\'re looking for'; //TODO: show error 404
			} else { $this->display($get_template); }
		} else {
			$this->display($template);
		}
	}

	//TODO: move this to helper ?
	public function check_is_file( $template ='' ) {
		$filename = dirname(dirname(__FILE__)).'/views/'.$template;
		if(is_file($filename)){
			return TRUE;
		}else{
			return FALSE;
		}
	}



	//public function get_head(){
	//	return $this->head;
	//}





	function in_development() {
		$this->da('main/default/development.htm', TRUE);
	}


}
