
function conlog(msg) {
	console.log(msg);
	return true;
}

function getServerDate() {
	var serverDate = new Date();
//	$.post(site_url+'idlec/get_server_date/array/', {}, function(ret){
//		serverdate = new Date(parseInt(ret.year), parseInt(ret.month) , parseInt(ret.day), parseInt(ret.hour),parseInt(ret.minute), parseInt(ret.second));
//	},'json');
// This is not the way to use javascript post callback
	return serverDate;
}

function get_time_difference(earlierDate,laterDate) {
	var nTotalDiff = laterDate.getTime() - earlierDate.getTime();
	var oDiff = new Object();

	oDiff.days = Math.floor(nTotalDiff/1000/60/60/24);
	nTotalDiff -= oDiff.days*1000*60*60*24;

	oDiff.hours = Math.floor(nTotalDiff/1000/60/60);
	nTotalDiff -= oDiff.hours*1000*60*60;

	oDiff.minutes = Math.floor(nTotalDiff/1000/60);
	nTotalDiff -= oDiff.minutes*1000*60;

	oDiff.seconds = Math.floor(nTotalDiff/1000);

	return oDiff;
}
