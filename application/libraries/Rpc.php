<?php

class Rpc {

	var $CI;

	function Rpc() {
		$this->CI =& get_instance();
	}

	function HTTPPost($url , $data = '' , $ref = '') {
		$ch = curl_init();
		//$proxy = getOneProxy();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Set supaya return value nya ke string
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if ($ref) curl_setopt($ch, CURLOPT_REFERER, $ref);
		else curl_setopt($ch, CURLOPT_REFERER, "http://www.markaskucing.com/");
		curl_setopt($ch , CURLOPT_USERAGENT , "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1");
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
		//curl_setopt($ch, CURLOPT_PROXY, $proxy);
		//echo "Using proxy $proxy\n";
		if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$retval = curl_exec($ch);
		curl_close($ch);
		return $retval;
	}

	function HTTPGet_($url , $ref = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Set supaya return value nya ke string
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if ($ref) curl_setopt($ch, CURLOPT_REFERER, $ref);
		else curl_setopt($ch, CURLOPT_REFERER, "http://www.markaskucing.com/");
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($ch , CURLOPT_USERAGENT , "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1");
		$retval = curl_exec($ch);
		curl_close($ch);
		return $retval;
	}

	function HTTPGet($url , $ref = '') {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Set supaya return value nya ke string
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.markaskucing.com/");
		if ($ref) curl_setopt($ch, CURLOPT_REFERER, $ref);
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($ch , CURLOPT_USERAGENT , "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    "X-Requested-With" => "XMLHttpRequest"
		));

		$retval = curl_exec($ch);
		curl_close($ch);
		return $retval;
	}

	function get_cache_url($url) {
		$this->CI->load->library('sitecache');
		$this->CI->sitecache->set_url($url);

		if ($cache = $this->CI->sitecache->get()) {
			$res = $cache;
		}
		else {
			$res = $this->HTTPGet($url);
			$this->CI->sitecache->add($res);
		}

		return $res;
	}

	function doREST_cache($url, $params) {
		if ($params) {
			foreach($params as $key => $param) {
				$_par[] = "$key=" . urlencode($param);
			}
			$_complete = "?";
			$_complete .= implode("&" , $_par);
		}
		return $this->get_cache_url($url . $_complete);
	}

	function doREST($url, $params) {
		if ($params) {
			foreach($params as $key => $param) {
				$_par[] = "$key=" . urlencode($param);
			}
			$_complete = "?";
			$_complete .= implode("&" , $_par);
		}
		echo $url . $_complete;
		return $this->HTTPGet($url . $_complete);
	}

	function doPostREST($url, $params , $ref = '') {
		if ($params) {
			foreach($params as $key => $param) {
				$_par[] = "$key=" . urlencode($param);
			}
			$_complete = '';
			$_complete .= implode("&" , $_par);
		}
		return $this->HTTPPost($url , $_complete , $ref);
	}

}
?>