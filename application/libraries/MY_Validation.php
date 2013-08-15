<?php

class MY_Validation extends CI_Validation {

	function MY_Validation()
	{
		parent::CI_Validation();
	}

	function min_value($str, $val)
	{
		return ($str >= $val) ? TRUE : FALSE;
	}
	
	function max_value($str, $val)
	{
		return ($str <= $val) ? TRUE : FALSE;
	}
	
	function creditcard($cardnumber) {
		$cardnumber=preg_replace("/\D|\s/", "", $cardnumber);  # strip any non-digits
		$cardlength=strlen($cardnumber);
		$parity=$cardlength % 2;
		$sum=0;
		for ($i=0; $i<$cardlength; $i++) {
			$digit=$cardnumber[$i];
			if ($i%2==$parity) $digit=$digit*2;
			if ($digit>9) $digit=$digit-9;
			$sum=$sum+$digit;
		}
		$valid=($sum%10==0);
		return $valid;
	}
	
}
?>