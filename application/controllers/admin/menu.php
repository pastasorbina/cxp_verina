<?php
class Menu extends MY_Controller {

	var $mod_title = 'Manage Menu';

	function __construct() {
		parent::__construct();
		$this->sci->init('admin');
		$this->_init();
		$this->session->validate(array('ADMIN'), 'admin');

		$this->docroot = $_SERVER['DOCUMENT_ROOT'];
		$this->realpath = REALPATH;

		//var_dump($_SERVER);
		if($this->realpath != $this->docroot) {
			$this->folderpath = ltrim($this->realpath, $this->docroot);
			$this->folderpath = rtrim($this->folderpath, '/');
		} else {
			$this->folderpath = '';
		}
		$this->sci->assign('folderpath' , $this->folderpath);

	}

	function index() {
		$this->db->join('branch' , 'branch.b_id = menu_position.b_id' , 'left');
		$this->db->where('mp_status' , 'Active');
		$this->db->where('branch.b_id' , $this->branch_id);
		$this->db->order_by('mp_id' , 'ASC');
		$res = $this->db->get('menu_position');
		$this->sci->assign('menu_position' , $res->result_array());

		//get all matches
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('m_status' , 'Active');
		$this->db->order_by('m_id');
		$res = $this->db->get('menu');
		$allmenu = $res->result_array();
		$matches = array();
		foreach($allmenu as $k=>$tmp) {
			$ack = '';
			preg_match('@^(?:http://)?([^/]+)@i', $tmp['m_link'], $ack);
			if(!in_array($ack[0], $matches) ) {
				$matches[] = $ack[0];
			}
		}
		$this->sci->assign('matches' , $matches);
		$this->sci->assign('http_host' , 'http://'.$_SERVER['HTTP_HOST']);

		$this->sci->da('index.htm');
	}

	function add_position() {

	}

	function load($mp_id = 0 , $parent_id = 0) {
		$this->db->where('mp_id' , $mp_id);
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('m_parent_id' , $parent_id);
		$this->db->where('m_status !=' , 'Deleted');
		$this->db->order_by('m_id');
		$res = $this->db->get('menu');

		if ($parent_id == 0) {
			echo "<a href='#' id='$parent_id' class='add_child' title='Add Child'>+ add menu</a>  ";
		}

		echo "<ul class=\"menu_list\" >";
		foreach($res->result() as $row) {
			echo '<li class="'.$row->m_status.'">';
			echo "<div class='tmenu'><a href='#' id='{$row->m_id}' class='edit_menu' title='Edit Menu'><i class='icon icon-edit'></i></a> <a href='#' id='{$row->m_id}' class='delete_menu' title='Delete this'><i class='icon icon-trash'></i></a> ";
			if($row->m_status == 'Active') {
				echo "<a href='#' id='{$row->m_id}' class='suspend_menu' title='Suspend this'><i class='icon icon-pause'></i></a>";
			} else {
				echo "<a href='#' id='{$row->m_id}' class='activate_menu' title='Activate this'><i class='icon icon-play'></i></a>";
			}
			echo "</div>";
			echo "<div class='name'>".$row->m_name."</div>";
			echo "<div class='link'>".$row->m_link."</div>";
			$this->load($mp_id , $row->m_id);
			echo "</li>";
		}
		echo "</ul>";

		if ($parent_id > 0) {
			echo "<div class='fmenu'>";
			echo "<a href='#' id='$parent_id' class='add_child' title='Add Child'>Add Child</a> | ";
			echo "<a href='#' id='$parent_id' class='move_up' title='Add Child'>Up</a> | ";
			echo "<a href='#' id='$parent_id' class='move_down' title='Add Child'>Down</a>";
			echo "</div>";
		}
	}

	function add_child() {
		$m_id = $this->input->post('m_id');
		$mp_id = $this->input->post('mp_id');

		// Load menu module
		$this->db->where('mm_status' , 'Active');
		$res = $this->db->get('menu_module');
		$menu_module = array();
		foreach($res->result() as $row) {
			$menu_module[$row->mm_id] = $row->mm_name;
		}
		$this->sci->assign('menu_module' , $menu_module);

		$this->sci->assign('m_id' , $m_id);
		$this->sci->assign('mp_id' , $mp_id);
		$this->sci->d('add_child.htm');
	}

	function move_up() {
		$m_id = $this->input->post('m_id');
		$mp_id = $this->input->post('mp_id');

		// Load menu info

		$this->db->where('m_id' , $m_id);
		$this->db->where('m_status' , 'Active');
		$res = $this->db->get('menu');
		if ($res->num_rows() > 0) {
			$menu = $res->row();
		} else {
			return;
		}

		// Load menu before this
		$this->db->where('m_id <' , $m_id);
		$this->db->where('mp_id' , $mp_id);
		$this->db->where('m_parent_id' , $menu->m_parent_id);
		$this->db->order_by('m_id' , 'desc');
		$res = $this->db->get('menu');

		if ($res->num_rows() > 0) {
			$menu2 = $res->row();
			// Change the menu
			$this->db->set('m_id' , 0);
			$this->db->where('m_id' , $m_id);
			$this->db->update('menu');

			$this->db->set('m_id' , $menu->m_id);
			$this->db->where('m_id' , $menu2->m_id);
			$this->db->update('menu');

			$this->db->set('m_id' , $menu2->m_id);
			$this->db->where('m_id' , 0);
			$this->db->update('menu');
		} else {
			return;
		}
	}

	function move_down() {
		$m_id = $this->input->post('m_id');
		$mp_id = $this->input->post('mp_id');

		// Load menu info
		$this->db->where('m_id' , $m_id);
		$this->db->where('m_status' , 'Active');
		$res = $this->db->get('menu');
		if ($res->num_rows() > 0) {
			$menu = $res->row();
		} else {
			return;
		}

		// Load menu before this
		$this->db->where('m_id >' , $m_id);
		$this->db->where('mp_id' , $mp_id);
		$this->db->where('m_parent_id' , $menu->m_parent_id);
		$this->db->order_by('m_id' , 'asc');
		$res = $this->db->get('menu');

		if ($res->num_rows() > 0) {
			$menu2 = $res->row();
			// Change the menu
			$this->db->set('m_id' , 0);
			$this->db->where('m_id' , $m_id);
			$this->db->update('menu');

			$this->db->set('m_id' , $menu->m_id);
			$this->db->where('m_id' , $menu2->m_id);
			$this->db->update('menu');

			$this->db->set('m_id' , $menu2->m_id);
			$this->db->where('m_id' , 0);
			$this->db->update('menu');
		} else {
			return;
		}
	}

	function module_content() {
		$mm_id = $this->input->post('mm_id');

		$this->db->where('mm_status' , 'Active');
		$this->db->where('mm_id' , $mm_id);
		$res = $this->db->get('menu_module');
		$menu_module = $res->row();


		if($menu_module->mm_table_name !='') {
			if ($menu_module->mm_join != '') {
				$join = $menu_module->mm_join;
				$join = explode('|', trim($join));
				foreach($join as $k=>$tmp) {
					$exploded = explode(':', trim($tmp));
					$one = isset($exploded[0]) ? $exploded[0] : NULL;
					$two = isset($exploded[1]) ? $exploded[1] : NULL;
					$three = isset($exploded[2]) ? $exploded[2] : 'left';
					if($one==NULL || $two==NULL ) {
					} else {
						$this->db->join( $one , $two , $three);
					}
				}
			}


			if ($menu_module->mm_status_field != '') {
				$this->db->where($menu_module->mm_status_field , 'Active');
				if ($menu_module->mm_usebranch == 'yes') {
					$this->db->where( $menu_module->mm_table_name.'.b_id' , $this->branch_id);
				}
			}

			if($menu_module->mm_where != '') {
				$where = $menu_module->mm_where;
				$where = explode('|', trim($where));
				foreach($where as $k=>$tmp) {
					$exploded = explode(':', trim($tmp));
					$one = isset($exploded[0]) ? $exploded[0] : NULL;
					$two = isset($exploded[1]) ? $exploded[1] : NULL;
					if($one==NULL || $two==NULL ) {
					} else {
						$this->db->where( $one , $two );
					}
				}
			}

			$res = $this->db->get($menu_module->mm_table_name);
			$data = $res->result_array();
			$maindata = array();
			foreach ($res->result_array() as $row) {
				//print_r($row);
				$key_field = '';
				$value_field = '';

				if( $menu_module->mm_append_with == 'Both' || $menu_module->mm_append_with == 'Key' ) {
					$key_field = isset($row[$menu_module->mm_key_field]) ? $row[$menu_module->mm_key_field].'/' : '';
				}

				if( $menu_module->mm_append_with == 'Both' || $menu_module->mm_append_with == 'Value' ) {
					$value_field = isset($row[$menu_module->mm_value_field]) ? $row[$menu_module->mm_value_field].'/' : '';
					$value_field = url_title(strtolower($value_field));
				}

				$maindata[$menu_module->mm_controller . $key_field . $value_field ] = $row[$menu_module->mm_value_field];
			}
		} else {
			$maindata = array();
			$maindata[$menu_module->mm_controller] = "[no module]";
		}

		$this->sci->assign('module_content' , $maindata);
		//print_r($maindata);

		$this->sci->d('module_content.htm');
	}

	function add_child_do() {
		$link_name = $this->input->post('link_name');
		$link_url = $this->input->post('link_url');
		$m_id = $this->input->post('m_id');
		$mm_id = $this->input->post('mm_id');
		$mp_id = $this->input->post('mp_id');

		$this->db->set('m_parent_id' , $m_id);
		$this->db->set('mp_id' , $mp_id);
		$this->db->set('b_id' , $this->branch_id);
		$this->db->set('m_name' , $link_name);
		$this->db->set('m_link' , $link_url);
		$this->db->insert('menu');
	}

	function delete_menu($m_id = 0) {
		$this->db->set('m_status' , 'Deleted');
		$this->db->where('m_id' , $m_id);
		$this->db->update('menu');
	}

	function suspend_menu($m_id = 0) {
		$this->db->set('m_status' , 'Suspended');
		$this->db->where('m_id' , $m_id);
		$this->db->update('menu');
	}

	function activate_menu($m_id = 0) {
		$this->db->set('m_status' , 'Active');
		$this->db->where('m_id' , $m_id);
		$this->db->update('menu');
	}

	function edit_menu() {
		$m_id = $this->input->post('m_id');
		$mp_id = $this->input->post('mp_id');

		// Load menu module
		$this->db->where('mm_status' , 'Active');
		$this->db->order_by('mm_name' , 'ASC');
		$res = $this->db->get('menu_module');

		$menu_module = array();
		foreach($res->result() as $row) {
			$menu_module[$row->mm_id] = $row->mm_name;
		}
		$this->sci->assign('menu_module' , $menu_module);

		// Load menu
		$this->db->where('m_id' , $m_id);
		$res = $this->db->get('menu');
		$this->sci->assign('menu' , $res->row_array());

		$this->sci->assign('m_id' , $m_id);
		$this->sci->assign('mp_id' , $mp_id);
		$this->sci->d('edit_menu.htm');
	}

	function edit_menu_do() {
		$link_name = $this->input->post('link_name');
		$link_url = $this->input->post('link_url');
		$m_id = $this->input->post('m_id');
		$mm_id = $this->input->post('mm_id');
		$mp_id = $this->input->post('mp_id');

		$this->db->set('m_name' , $link_name);
		$this->db->set('m_link' , $link_url);
		$this->db->where('m_id' , $m_id);
		$this->db->update('menu');
	}



	function replace_url($link='') {
		$link = safe_base64_decode($link);

		$url = array();
		$url = isset($_POST['url']) ? $_POST['url'] : array();
		$use_folder = isset($_POST['use_folder']) ? $_POST['use_folder'] : 'no';
		if($use_folder == 'yes') { $folder = "/".$this->folderpath; } else { $folder = ""; }

		$link = safe_base64_decode($url);
		$this->db->where('b_id' , $this->branch_id);
		$this->db->like('m_link' , $link);
		$this->db->where('m_status' , 'Active');
		$this->db->order_by('m_id');
		$res = $this->db->get('menu');
		$allmenu = $res->result_array();
		//print_r($allmenu);
		//print "<hr>";
		//$rx = "([^\"]*)\">(.*)";
		//
		////preg_match('@^(?:http://)?([^/]+)@i', "http://www.php.net/index.html", $matches);
		////$host = $matches[1];
		//
		//
		$matches = array();
		foreach($allmenu as $k=>$tmp) {
			$m_link = $tmp['m_link'];
			$m_link = str_replace($link.$folder."/", 'http://'.$_SERVER['HTTP_HOST']."/", $m_link);
			//$matches[$k] = $m_link;
			$this->db->where('m_id' , $tmp['m_id']);
			$this->db->set('m_link' , $m_link);
			$this->db->update('menu');
		}
		redirect($this->mod_url.'index');

	}

	function replace_url2() {
		if( $_POST['from'] == '' || $_POST['to'] == '' )  {
			show_error('must input both from and to');
		}
		$from = $_POST['from'];
		$to = $_POST['to'];
		//
		//print $from; print $to;
		//exit();
		$this->db->where('b_id' , $this->branch_id);
		$this->db->like('m_link' , $from);
		//$this->db->where('m_status' , 'Active');
		$this->db->order_by('m_id');
		$res = $this->db->get('menu');
		$allmenu = $res->result_array();

		$matches = array();
		foreach($allmenu as $k=>$tmp) {
			$m_link = $tmp['m_link'];
			$m_link = str_replace($from, $to, $m_link);
			$this->db->where('m_id' , $tmp['m_id']);
			$this->db->set('m_link' , $m_link);
			$this->db->update('menu');
		}
		redirect($this->mod_url.'index');
	}

}

?>
