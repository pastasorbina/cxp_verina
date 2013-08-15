<?php
/* helpers specific to current project site */
/* all helpers have prefix ss_ so it won't conflict with other functions*/





function ss_reformat_date($datetime = NULL, $delimiter='.') {
	$result = '';
	if($datetime) {
		$datetime = explode(' ',$datetime);
		$result = str_replace('-', $delimiter, $datetime[0]);
	}
	return $result;
}

function get_time_difference( $start, $end )
	{
	    $uts['start']      =    strtotime( $start );
	    $uts['end']        =    strtotime( $end );
	    if( $uts['start']!==-1 && $uts['end']!==-1 )
	    {
		if( $uts['end'] >= $uts['start'] )
		{
		    $diff    =    $uts['end'] - $uts['start'];
		    if( $days=intval((floor($diff/86400))) )
			$diff = $diff % 86400;
		    if( $hours=intval((floor($diff/3600))) )
			$diff = $diff % 3600;
		    if( $minutes=intval((floor($diff/60))) )
			$diff = $diff % 60;
		    $diff    =    intval( $diff );
		    return( array('days'=>$days, 'hours'=>$hours, 'minutes'=>$minutes, 'seconds'=>$diff) );
		}
		else
		{
		    //trigger_error( "Ending date/time is earlier than the start date/time", E_USER_WARNING );
		}
	    }
	    else
	    {
		//trigger_error( "Invalid date/time data detected", E_USER_WARNING );
	    }
    return( false );
}


function dateTimeDiff($data_ref){

	// Get the current date
	$current_date = date('Y-m-d H:i:s');

	// Extract from $current_date
	$current_year = substr($current_date,0,4);
	$current_month = substr($current_date,5,2);
	$current_day = substr($current_date,8,2);

	// Extract from $data_ref
	$ref_year = substr($data_ref,0,4);
	$ref_month = substr($data_ref,5,2);
	$ref_day = substr($data_ref,8,2);

	// create a string yyyymmdd 20071021
	$tempMaxDate = $current_year . $current_month . $current_day;
	$tempDataRef = $ref_year . $ref_month . $ref_day;

	$tempDifference = $tempMaxDate-$tempDataRef;

	// If the difference is GT 10 days show the date
	if($tempDifference >= 10){
		echo $data_ref;
	} else {

		// Extract $current_date H:m:ss
		$current_hour = substr($current_date,11,2);
		$current_min = substr($current_date,14,2);
		$current_seconds = substr($current_date,17,2);

		// Extract $data_ref Date H:m:ss
		$ref_hour = substr($data_ref,11,2);
		$ref_min = substr($data_ref,14,2);
		$ref_seconds = substr($data_ref,17,2);

		$hDf = $current_hour-$ref_hour;
		$mDf = $current_min-$ref_min;
		$sDf = $current_seconds-$ref_seconds;
		//$dDf

		// Show time difference ex: 2 min 54 sec ago.
		if($dDf<1){
			if($hDf>0){
				if($mDf<0){
					$mDf = 60 + $mDf;
					$hDf = $hDf - 1;
					echo $mDf . ' min ago';
				} else {
					echo $hDf. ' hr ' . $mDf . ' min ago';
				}
			} else {
				if($mDf>0){
					echo $mDf . ' min ' . $sDf . ' sec ago';
				} else {
					echo $sDf . ' sec ago';
				}
			}
		} else {
			echo $dDf . ' days ago';
		}
	}

}
?>
