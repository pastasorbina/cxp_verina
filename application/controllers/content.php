<?php

class Content extends MY_Controller {

	var $mod_title = '';

	function __construct() {
		parent::__construct();
		$this->sci->set_room('main');
		$this->_init();
		$this->session->validate_member(FALSE);
		$this->sci->assign('mod_title' , $this->mod_title);

	}

	function view($cl_code='article', $c_id=0, $c_slug='' ) {

		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_code' , $cl_code);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();

		switch($content_label['cl_type']) {
			case 'article' :
				if($c_id == 0) {
					if(make_slug($cl_code) == 'local_event_calendar') {
						//$this->view_list_cal($cl_code);
						redirect($this->mod_url. "view_list_cal/$cl_code");
					} else {
						$this->view_list($cl_code);
					}

				} else {
					$this->view_article($c_id);
				}
				break;
			case 'page' :
				$this->view_page($c_id);
				break;
		}
	}

	function view_article($c_id=0) {
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('c_id' , $c_id);
		if($this->branch_id != 1) { $this->db->where('content.b_id' , $this->branch_id); }
		$this->db->where('c_status' , 'Active');
		$res = $this->db->get('content');
		$content = $res->row_array();

		if(!$content) { show_404(); }

		$this->sci->assign('module_title' , $content['c_title']);

		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('content.cl_id' , $content['cl_id']);
		$this->db->where('content.c_id !=' , $c_id);
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$this->db->limit(6, 0);
		$this->db->order_by('c_date' , 'DESC');
		$res = $this->db->get('content');
		$other_article = $res->result_array();
		foreach($other_article as $k2=>$tmp2) {
			$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
			$this->db->where('mr_foreign_id' , $other_article[$k2]['c_id']);
			$this->db->where('mr_module' , 'content');
			$res = $this->db->get('media_relation');
			$media = $res->result_array();
			foreach($media as $l=>$tmp3) {
				$pos = $tmp3['mr_pos'];
				$other_article[$k2]['media'][$pos] = $tmp3;
			}
		}
		$this->sci->assign('other_article' , $other_article);


		$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
		$this->db->where('mr_foreign_id' , $content['c_id']);
		$this->db->where('mr_module' , 'content');
		$res = $this->db->get('media_relation');
		$media = $res->result_array();
		foreach($media as $l=>$tmp) {
			$pos = $tmp['mr_pos'];
			$content['media'][$pos] = $tmp;
		}

		$this->sci->assign('content' , $content);

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "<a href='".site_url()."content/view_list/".$content['cl_code']."/' >".$content['cl_name']."</a>";
		$breadcrumb[] = $content['c_title'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('article_view.htm');
	}


	function view_list( $cl_code='article', $offset=0, $orderby='c_date', $ascdesc='DESC', $encodedkey='' ) {

		if($cl_code == 'schedule') {
			redirect($this->mod_url. "view_list_cal/$cl_code");
		} elseif( $cl_code == 'size_chart') {
			redirect($this->mod_url. "view_list_size_chart/");
		}

		$this->_load_sidebar();

		$this->db->where('cl_code' , $cl_code);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();


		if(!$content_label) { show_404(); }
		$this->sci->assign('content_label' , $content_label);

		//sidebar, get all other content label excet this
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_status' , 'Active');
		$this->db->where('cl_type' , 'article');
		$this->db->where('cl_id !=' , $content_label['cl_id']);
		$res = $this->db->get('content_label');
		$other_cl = $res->result_array();

		foreach($other_cl as $k=>$tmp) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('content.cl_id' , $tmp['cl_id']);
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
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


		$pagelimit = $this->site_config['ARTICLE_PAGELIMIT'];
		$this->db->start_cache();
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('content.cl_id' , $content_label['cl_id']);
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
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

		$total = $this->db->count_all_results('content');
		$this->load->library('pagination');
		$config['base_url'] = $this->mod_url."view_list/$cl_code/";
		$config['suffix'] = "/$orderby/$ascdesc/$encodedkey" ;
		$config['total_rows'] = $total;
		$config['per_page'] = $pagelimit;
		$config['uri_segment'] = 4;
		$this->pagination->initialize($config);

		$this->db->limit($pagelimit, $offset);
		$res = $this->db->get('content');
		$this->db->flush_cache();
		$maindata = $res->result_array();
		foreach($maindata as $k=>$tmp) {
			$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
			$this->db->where('mr_foreign_id' , $maindata[$k]['c_id']);
			$this->db->where('mr_module' , 'content');
			$res = $this->db->get('media_relation');
			$media = $res->result_array();
			foreach($media as $l=>$tmp) {
				$pos = $tmp['mr_pos'];
				$maindata[$k]['media'][$pos] = $tmp;
			}
		}
		$this->sci->assign('maindata' , $maindata);
		$this->sci->assign('paging', $this->pagination->create_links() );

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $content_label['cl_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		$this->sci->da('article_list.htm');
	}


	function view_list_cal( $cl_code='article', $year=0, $month=0 ) {

		//$this->_load_sidebar();

		$this->load->helper('date');
		//print date_format(time(), 'Y m d');
		if($year == 0) $year =  mdate( '%Y', time() );
		if($month == 0) $month =  mdate( '%m', time() );

		$this->db->where('cl_code' , $cl_code);
		$res = $this->db->get('content_label');
		$content_label = $res->row_array();
		//print_r($content_label);

		if(!$content_label) { show_404(); }
		$this->sci->assign('content_label' , $content_label);

		//sidebar, get all other content label excet this
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_status' , 'Active');
		$this->db->where('cl_type' , 'article');
		$this->db->where('cl_id !=' , $content_label['cl_id']);
		$res = $this->db->get('content_label');
		$other_cl = $res->result_array();

		foreach($other_cl as $k=>$tmp) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('content.cl_id' , $tmp['cl_id']);
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
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


		$pagelimit = $this->site_config['ARTICLE_PAGELIMIT'];

		$this->db->start_cache();
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('content.cl_id' , $content_label['cl_id']);
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('YEAR(c_date)' , $year);
		$this->db->where('MONTH(c_date)' , $month);
		$this->db->where('c_status' , 'Active');
		$this->db->stop_cache();

		$this->db->select('DAY(c_date) as date');
		$this->db->group_by('date');
		$res = $this->db->get('content');
		$datax = $res->result_array();
		$data = array();
		$cell = array();
		foreach($datax as $k=>$tmp) {
			$this->db->where('DAY(c_date)' , $tmp['date']);
			$res = $this->db->get('content');
			$art = $res->result_array();
			$cell[$tmp['date']] = '#';

		$str = '';
		//$str = '<ul>';
			foreach($art as $k2=>$tmp2) {
				//$str .= '<li><a href="'.site_url().'content/view/'.$tmp2['cl_code'].'/'.$tmp2['c_id'].'/'.make_slug($tmp2['c_title']).'">'. substr_replace($tmp2['c_title'], '', 50, strlen($tmp2['c_title'])).'</a></li>';
				$text = substr_replace($tmp2['c_title'], '', 50, strlen($tmp2['c_title']));
				$str .= '<li><a href="#" class="label warning">'.$text.'</a></li>';
				//print $str;
			}
			//$str .= '</ul>';
			$cell[$tmp['date']] = $str;
		}
		foreach($cell as $k=>$tmp){
			$a = $cell[$k];
			$a = '<ul>'.$a.'</ul>';
			$cell[$k] = $a;
		}
		//print_r($data);

		$this->sci->assign('data' , $data);
		$this->sci->assign('datax' , $datax);


		$this->db->flush_cache();

		//assign breadcrumb
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = $content_label['cl_name'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		//make calendar
		$cal_template = $this->sci->fetch('main/content/calendar_data.php');

		$prefs['template'] = $cal_template;

		$prefs = array (
				'template' => $cal_template,
				'show_next_prev'  => TRUE,
				'next_prev_url'   => $this->mod_url."view_list_cal/$cl_code"
             );
		$this->load->library('calendar', $prefs);

		 //var_dump($cell);

		$calendar = $this->calendar->generate($year,$month, $cell);
		$this->sci->assign('calendar' , $calendar);

		$this->sci->da('article_list_cal.htm');
	}


	function view_page($c_id=0) {

		//get product images (from content placeholder)
		//$this->db->where('content.c_id' , $c_id);
		//$this->db->join('content' , 'content.c_id = content_image.c_id' , 'left');
		//$this->db->order_by('ci_order' , 'asc');
		//$res = $this->db->get('content_image');
		//$content_image = $res->result_array();
		//$this->sci->assign('content_image' , $content_image);

		$this->_load_sidebar();
		//
		//get content
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('c_id' , $c_id);
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$res = $this->db->get('content');
		$content = $res->row_array();
		if(!$content) { show_404(); }

		$this->sci->assign('module_title' , $content['c_title']);


		////get media
		//$this->db->join('media' , 'media.m_id = media_relation.m_id' , 'left');
		//$this->db->where('mr_foreign_id' , $content['c_id']);
		//$this->db->where('mr_module' , 'content');
		//$res = $this->db->get('media_relation');
		//$media = $res->result_array();
		//foreach($media as $l=>$tmp) {
		//	$pos = $tmp['mr_pos'];
		//	$content['media'][$pos] = $tmp;
		//}
		//
		////get options
		//$this->db->where('b_id' , $this->branch_id);
		//$this->db->where('c_id' , $content['c_id']);
		//$this->db->where('co_status' , "Active");
		//$res = $this->db->get('content_option');
		//$options = $res->result_array();
		//foreach($options as $k=>$tmp) {
		//	$opt = $tmp['co_key'];
		//	$content['option'][$opt] = $tmp;
		//}
		//
		$this->sci->assign('content' , $content);
		//
		////get parent
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('c_id' , $content['c_parent_id']);
		$this->db->where('c_status' , "Active");
		$res = $this->db->get('content');
		$parent = $res->row_array();
		//
		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		if($parent) {
			$breadcrumb[] = $parent['c_title'];
		}
		$breadcrumb[] = $content['c_title'];
		$this->sci->assign('breadcrumb' , $breadcrumb);

		//assign content
		$this->sci->assign('content' , $content);
		//display
		$this->sci->da('page_view.htm');
	}


	function _load_size_chart_sidebar($c_id=0){
		$this->sci->assign('c_id' , $c_id);
		//get root size_chart
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('cl_code' , 'size_chart');
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$this->db->where('c_parent_id' , 0);
		$res = $this->db->get('content');
		$sidebar_content = $res->result_array();
		foreach($sidebar_content as $k=>$tmp) {
			//get children content
			$this->db->where('c_parent_id' , $tmp['c_id']);
			$this->db->where('c_status' , 'Active');
			$res = $this->db->get('content');
			$child = $res->result_array();
			$sidebar_content[$k]['child'] = $child;
		}
		$this->sci->assign('sidebar_content' , $sidebar_content);
		$sidebar = $this->sci->fetch('content/size_chart_sidebar.htm');
		$this->sci->assign('size_chart_sidebar' , $sidebar);
		return true;
	}

	function view_list_size_chart($c_parent_id = 0) {

		// if parent_id == 0, get first
		if($c_parent_id == 0) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('cl_code' , 'size_chart');
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->where('c_parent_id' , 0);
			$res = $this->db->get('content');
			$first = $res->row_array();
			$c_parent_id = $first['c_id'];
		}
		$this->_load_size_chart_sidebar($c_parent_id);
		$this->sci->assign('c_parent_id' , $c_parent_id);

		//get content
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('cl_code' , 'size_chart');
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$this->db->where('c_parent_id' , $c_parent_id);
		$res = $this->db->get('content');
		$content = $res->result_array();

		//get root content
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('cl_code' , 'size_chart');
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$this->db->where('c_id' , $c_parent_id);
		$res = $this->db->get('content');
		$root_content = $res->row_array();
		$this->sci->assign('root_content' , $root_content);

		//get children content
		$this->db->where('c_parent_id' , $c_parent_id);
		$this->db->where('c_status' , 'Active');
		$res = $this->db->get('content');
		$child_content = $res->result_array();
		$this->sci->assign('child_content' , $child_content);

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = "Panduan Ukuran";
		$this->sci->assign('breadcrumb' , $breadcrumb);

		//assign content
		$this->sci->assign('content' , $content);
		//display
		$this->sci->da('size_chart_list.htm');
	}

	function view_size_chart($c_id = 0) {

		// if parent_id == 0, get first
		if($c_id == 0) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('cl_code' , 'size_chart');
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->where('c_parent_id' , 0);
			$res = $this->db->get('content');
			$first = $res->row_array();
			$c_id = $first['c_id'];
		}
		$this->_load_size_chart_sidebar($c_id);
		$this->sci->assign('c_id' , $c_id);

		//get content
		$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
		$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
		$this->db->where('cl_code' , 'size_chart');
		$this->db->where('content.b_id' , $this->branch_id);
		$this->db->where('c_status' , 'Active');
		$this->db->where('c_id' , $c_id);
		$res = $this->db->get('content');
		$size_chart = $res->row_array();
		$this->sci->assign('size_chart' , $size_chart);

		//get parent
		if($size_chart) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->join('user' , 'user.u_id = content.c_author_id' , 'left');
			$this->db->where('cl_code' , 'size_chart');
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->where('c_id' , $size_chart['c_parent_id']);
			$res = $this->db->get('content');
			$size_chart_parent = $res->row_array();
			$this->sci->assign('size_chart_parent' , $size_chart_parent);
		}

		$breadcrumb = array();
		$breadcrumb[] = "<a href='".site_url()."' >Home</a>";
		$breadcrumb[] = '<a href="'.site_url().'content/view_size_chart/">Panduan Ukuran</a>';
		if($size_chart_parent) {
			$breadcrumb[] = '<a href="'.site_url().'content/view_size_chart/'.$size_chart_parent['c_id'].'">'.$size_chart_parent['c_title']."</a>";
		}

		if($size_chart) {
			$breadcrumb[] = '<a href="'.site_url().'content/view_size_chart/'.$size_chart['c_id'].'">'.$size_chart['c_title'].'</a>';
		}


		$this->sci->assign('breadcrumb' , $breadcrumb);

		//assign content


		//display
		$this->sci->da('size_chart_view.htm');
	}


	function feed($cl_code='news') {
		$head = "<?xml version='1.0' encoding='UTF-8' ?><rss version='2.0'><channel>";
		$tail = "</channel></rss>";
		$string = "";

		switch( $this->branch_id ) {
			case '1' : $lang = 'en-us'; break;
			case '2' : $lang = 'ind'; break;
		}
		$date = date('D, d M Y H:i:s T');

		//$language = $this->branch['b_code'];
		//<pubDate>Mon, 26 Mar 2007 11:51:08 GMT</pubDate>
		$string .= "<title>".$cl_code."</title>";
		$string .= "<link>".site_url()."</link>";
		$string .= "<description> </description>";
		$string .= "<copyright>Copyright 2012, Medan Focal Point</copyright>";
		$string .= "<generator>Hiubang Marketing Communications</generator>";
		$string .= "<pubDate>".$date."</pubDate>";
		$string .= "<language>En</language>";
		$string .= "<docs></docs>";


		$this->db->where('cl_code' , $cl_code);
		$this->db->where('b_id' , $this->branch_id);
		$this->db->where('cl_status' , 'Active');
		$res = $this->db->get('content_label');
		$cl = $res->row_array();

		if($cl) {
			$this->db->join('content_label' , 'content_label.cl_id = content.cl_id' , 'left');
			$this->db->where('content.cl_id' , $cl['cl_id']);
			$this->db->where('content.b_id' , $this->branch_id);
			$this->db->where('c_status' , 'Active');
			$this->db->where('c_publish_status' , 'Published');
			$this->db->limit(7);
			$this->db->order_by('c_date' , 'desc');
			$res = $this->db->get('content');
			$content = $res->result_array();
		//print_r($content);
		} else {
			$content = array();
		}

		foreach($content as $k=>$tmp) {
			$string .="<item>";
			$string .="<title>".trim(xss_clean(htmlentities($tmp['c_title'])))."</title>";
			$string .="<description>".trim(strip_tags(xss_clean(htmlentities($tmp['c_content_intro']))))."</description>";
			$string .="<link>".site_url()."content/view/".$cl_code."/".$tmp['c_id']."/".make_slug($tmp['c_title'])."</link>";
			$string .="<pubDate>".$tmp['c_date']."</pubDate>";
			$string .="</item>";
		}

		$string = $head.$string.$tail;
		$string = trim($string);

		header ("Content-Type:text/xml");
		print $string;

	}

}
