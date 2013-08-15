<?php

/* extending url helper */

if ( ! function_exists('asset_url'))
{
	function asset_url($uri = '')
	{
		$CI =& get_instance();
		//return $CI->config->asset_url($uri);
		return $CI->config->item('asset_url'); 
	}
}
?>
