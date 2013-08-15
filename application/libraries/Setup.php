<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Branch Class
 * manage branching for multi site
 *
 * @author	William
 *
 */

class Setup  {

	public $CI;
	public $branch_id = 0;

	function __construct() {
		$this->CI =& get_instance();

		$this->branch_id = BRANCH_ID;
		$this->_set_default();

	}

	function check_config() {
		if(empty($this->default_cat) && empty($this->default_conf)) return FALSE;

		$this->CI->db->where('b_id' , $this->branch_id);
		$this->CI->db->where('c_type' , 'category');
		$res = $this->CI->db->get('config');
		$config = $res->result_array();
		if(!$config) {
			foreach($this->default_cat as $k=>$tmp) {
				$this->CI->db->set('b_id' , $this->branch_id);
				$this->CI->db->set('c_type' , 'category');
				foreach($tmp as $key=>$val) {
					$this->CI->db->set($key , $val);
				}
				$this->CI->db->insert('config');
			}
		}

		$this->CI->db->where('b_id' , $this->branch_id);
		$this->CI->db->where('c_type' , 'config');
		$res = $this->CI->db->get('config');
		$config = $res->result_array();
		if(!$config) {
			foreach($this->default_conf as $k=>$tmp) {
				$this->CI->db->set('b_id' , $this->branch_id);
				$this->CI->db->set('c_type' , 'config');
				foreach($tmp as $key=>$val) {
					$this->CI->db->set($key , $val);
				}
				$this->CI->db->insert('config');
			}
		}


	}

	function _set_default() {
		//default category
		$this->default_cat[] = array(
			'c_key'			=> 'site',
			'c_value'		=>	'site',
			'c_name'		=>	'Site',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'',
			'c_order'		=>	'1'
		);

		$this->default_cat[] = array(
			'c_key'			=> 'element',
			'c_value'		=>	'element',
			'c_name'		=>	'Element',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'',
			'c_order'		=>	'2'
		);

		$this->default_cat[] = array(
			'c_key'			=> 'social_media',
			'c_value'		=>	'social_media',
			'c_name'		=>	'Social Media',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'',
			'c_order'		=>	'3'
		);
		$this->default_cat[] = array(
			'c_key'			=> 'app',
			'c_value'		=>	'app',
			'c_name'		=>	'Application',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'',
			'c_order'		=>	'4'
		);




		//default config
		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_TITLE',
			'c_value'		=>	'',
			'c_name'		=>	'Site Title',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'site',
			'c_order'		=>	'1'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'META_DESCRIPTION',
			'c_value'		=>	'',
			'c_name'		=>	'Site Description',
			'c_valuetype'	=>	'textarea',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'site',
			'c_order'		=>	'2'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'META_KEYWORD',
			'c_value'		=>	'',
			'c_name'		=>	'Keyword',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'keywords associated with site, separated by comma',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'site',
			'c_order'		=>	'3'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_EMAIL',
			'c_value'		=>	'',
			'c_name'		=>	'Administrator\'s email',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'site',
			'c_order'		=>	'4'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_COPYRIGHT',
			'c_value'		=>	'',
			'c_name'		=>	'Copyright Text',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'site',
			'c_order'		=>	'5'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'HIT_COUNTER',
			'c_value'		=>	'',
			'c_name'		=>	'Hit Counter',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'no',
			'c_cat'			=>	'site',
			'c_order'		=>	'6'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_LOGO',
			'c_value'		=>	'',
			'c_name'		=>	'Site logo',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'1'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_FAVICON',
			'c_value'		=>	'',
			'c_name'		=>	'Favicon',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'2'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_BG1',
			'c_value'		=>	'',
			'c_name'		=>	'Background 1',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'3'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_BG2',
			'c_value'		=>	'',
			'c_name'		=>	'Background 2',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'4'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_BG3',
			'c_value'		=>	'',
			'c_name'		=>	'Background 3',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'5'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'MAIN_BG4',
			'c_value'		=>	'',
			'c_name'		=>	'Background 4',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'element',
			'c_order'		=>	'6'
			);

		//social media
		$this->default_conf[] = array(
			'c_key'			=> 'FACEBOOK_URL',
			'c_value'		=>	'',
			'c_name'		=>	'Facebook URL',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'make sure to use http:// in the beginning of url',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'1'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'TWITTER_URL',
			'c_value'		=>	'',
			'c_name'		=>	'Twitter URL',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'make sure to use http:// in the beginning of url',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'2'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YOUTUBE_URL',
			'c_value'		=>	'',
			'c_name'		=>	'Youtube URL',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'make sure to use http:// in the beginning of url',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'3'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YM1',
			'c_value'		=>	'',
			'c_name'		=>	'Yahoo Messenger 1',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'4'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YM2',
			'c_value'		=>	'',
			'c_name'		=>	'Yahoo Messenger 2',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'5'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YM3',
			'c_value'		=>	'',
			'c_name'		=>	'Yahoo Messenger 3',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'6'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YM4',
			'c_value'		=>	'',
			'c_name'		=>	'Yahoo Messenger 4',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'7'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'YM5',
			'c_value'		=>	'',
			'c_name'		=>	'Yahoo Messenger 5',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'social_media',
			'c_order'		=>	'8'
			);

		//app
		$this->default_conf[] = array(
			'c_key'			=> 'APP_TITLE',
			'c_value'		=>	'',
			'c_name'		=>	'App Title',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'1'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_NAME',
			'c_value'		=>	'',
			'c_name'		=>	'App Name',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'2'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_COPYRIGHT',
			'c_value'		=>	'',
			'c_name'		=>	'App Copyright',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'3'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_VERSION',
			'c_value'		=>	'',
			'c_name'		=>	'App Version',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'4'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_RELEASE',
			'c_value'		=>	'',
			'c_name'		=>	'App Release Status',
			'c_valuetype'	=>	'text',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'5'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_LOGO',
			'c_value'		=>	'',
			'c_name'		=>	'App Logo',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'6'
			);

		$this->default_conf[] = array(
			'c_key'			=> 'APP_FAVICON',
			'c_value'		=>	'',
			'c_name'		=>	'App Favicon',
			'c_valuetype'	=>	'image',
			'c_helptext'	=>	'',
			'c_editable'	=>	'yes',
			'c_cat'			=>	'app',
			'c_order'		=>	'7'
			);
	}




}
?>
