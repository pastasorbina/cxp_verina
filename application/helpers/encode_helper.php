<?php

	function safe_base64_encode($key = '') {
		$key = base64_encode($key);
		$key = str_replace('=', '', $key);
		$key = str_replace('+','-', $key);
		$key = str_replace('/','_', $key);
		return $key;
	}

	function safe_base64_decode($key = '') {
		$key = str_replace('-','+', $key);
		$key = str_replace('_','/', $key);
		$key = base64_decode($key);
		return $key;
	}

	function decode_tags($string) {
		$string = str_replace('&lt;','<', $string);
		$string = str_replace('&gt;','>', $string);
		$string = str_replace('&amp;','&', $string);
		return $string;
	}

	function remove_symbols($string) {
		$string =  preg_replace("/[^a-zA-Z0-9\s]/", "", $string);
		return $string;
	}

	function generate_luhn($number, $fullstring=TRUE) {
		$dbl = array(0 , 2 , 4 , 6 , 8 , 1 , 3 , 5 , 7 , 9);

		$sum = 0;
		$alternate = FALSE;
		$s = $number . '0';
		for ($i = strlen($s); $i >= 1 ; $i--) {
			if ($alternate) {
				$sum = $sum + $dbl[$s[$i - 1]];
			}
			else {
				$sum = $sum + $s[$i - 1];
			}
			$alternate = !$alternate;
		}
		$luhn_num = ((10 - ($sum % 10)) % 10);
		if($fullstring == FALSE) {
			return (string)$luhn_num;
		} else {
			return (string)$number.$luhn_num;
		}

	}

?>
