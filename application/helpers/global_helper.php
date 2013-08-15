<?php

function code_segment($code, $segment = 4)
{
    $temp = "";
    for ($i = 0 ; $i < strlen($code) ; $i++) {
		if (($i % 4) == 0 && $i > 0) $temp .= " ";
		$temp .= $code[$i];
	}

	return $temp;
}


?>
