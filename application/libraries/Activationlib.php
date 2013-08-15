<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Activationlib  {

	var $error_msg;

	function Activationlib() {
		$this->CI =& get_instance();
		if (!extension_loaded("curl")) dl("curl.so"); // USed for MWN
	}
	
	function HTTPPost($data , $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Set supaya return value nya ke string
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$retval = curl_exec($ch);
		curl_close($ch);
		
		return $retval;
	}

	function HTTPGet($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Set supaya return value nya ke string
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		$retval = curl_exec($ch);
		curl_close($ch);

		return $retval;
	}
	
	function doREST($url, $params) {
		if ($params) {
			foreach($params as $key => $param) {
				$_par[] = "$key=" . urlencode($param);
			}
			$_complete = "?";
			$_complete .= implode("&" , $_par);
		}
		return $this->HTTPGet($url . $_complete);
	}
	
	function doRESTPost($url, $params) {
		if ($params) {
			foreach($params as $key => $param) {
				$_par[] = "$key=" . urlencode($param);
			}
			$_complete = "";
			$_complete .= implode("&" , $_par);
		}
		
		return $this->HTTPPost($_complete , $url);
	}
	
	function model1($data) {
		$res = $this->doRESTPost("http://www.bak2u.com/activatePhonebak.php" , $data);
		
		if (preg_match("/<span class='errorMessage'>([^<]+)<\/span>/" , $res , $matches)) {
			$this->error_msg = $matches[1];
			return false;
		}
		else {
			return true;
		}
	}
	
	function model2($data) {
		$res = $this->doRESTPost("http://www.bak2u.com/activatePhonebak2.php" , $data);
		
		if (preg_match("/<span class='errorMessage'>([^<]+)<\/span>/" , $res , $matches)) {
			$this->error_msg = $matches[1];
			return false;
		}
		else {
			return true;
		}	
	}
	
	function activate_verey_mac($data) {
		$res = $this->doRESTPost("http://www.bak2u.com/activateVerey.php" , $data);
		
		if (preg_match("/<span class='errorMessage'>([^<]+)<\/span>/" , $res , $matches)) {
			$this->error_msg = $matches[1];
			return false;
		}
		else {
			return true;
		}
	}

	function activate_verey_pc($data) {
		$res = $this->doRESTPost("http://www.bak2u.com/activateVereypc.php" , $data);
		
		if (preg_match("/<span class='errorMessage'>([^<]+)<\/span>/" , $res , $matches)) {
			$this->error_msg = $matches[1];
			return false;
		}
		else {
			return true;
		}		
	}
	
	function activate_gadgettrak($data) {
		$res = $this->doRESTPost("https://account.gadgettheft.com/bak2u/index.php" , $data);
		
		var_dump($res);
		
		die();
		
		if (preg_match("/<span style=\"color: #ff0000\">The ([^<]+)<\/span>/" , $res , $matches)) {
			$this->error_msg = $matches[1];
			return false;
		}
		else {
			return true;
		}	
	}
	
}
?>
