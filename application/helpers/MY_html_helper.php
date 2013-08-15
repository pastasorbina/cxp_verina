<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function make_option_day($selected="") {
	$html = "";
	$html .= '<option value="0">-day-</option>';
	for($i=1; $i<=31; $i++) {
		if($selected == $i) { $is_selected = 'selected="selected"'; } else { $is_selected = ""; }
		$html .= '<option value="'.$i.'" '.$is_selected.' >'.$i.'</option>';
	}
	return $html;
}

function make_option_month($selected="") {
	$month = array(
		"January",
		"February",
		"March",
		"April",
		"May",
		"June",
		"July",
		"August",
		"September",
		"October",
		"November",
		"December",
	);
	$html = "";
	$html .= '<option value="0">-month-</option>';
	for($i=1; $i<=12; $i++) {
		if($selected == $i) { $is_selected = 'selected="selected"'; } else { $is_selected = ""; }
		$html .= '<option value="'.$i.'" '.$is_selected.' >'.$month[$i-1].'</option>';
	}
	return $html;
}

function make_option_year($selected="") {
	$start = date('Y');
	$count = 70;
	$html = "";
	$html .= '<option value="0">-year-</option>';
	for($i=$start; $i>=($start-$count); $i--) {
		if($selected == $i) { $is_selected = 'selected="selected"'; } else { $is_selected = ""; }
		$html .= '<option value="'.$i.'" '.$is_selected.' >'.$i.'</option>';
	}
	return $html;
}

function debug_html() {
	return "asdasd";
}
