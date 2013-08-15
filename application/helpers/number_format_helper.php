<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function strip_zero($str) {
		return preg_replace('/\.?0+$/' , '' , $str);
	}

	function price_format($price) {
		return number_format($price, 0, '', '.');
	}
