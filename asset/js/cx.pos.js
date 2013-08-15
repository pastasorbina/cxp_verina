	// 1 For debugging local , 0 for real env
	var debug = 0;

	function kick_drawer() {
		if (debug == 0) {
			// Kick the baby!!
			external.RC('kick' , 0 , 0 ,0);
		}
		else {
	 		alert('kicking');
		}
	}

	function rcprinter(data) {
		if (debug == 0) {
			external.RC('print' , data , 1 ,0);
		}
		else {
	 		alert(data);
		}
	}

	function rchtmlprinter(data) {
		if (debug == 0) {
			external.RC('printhtml' , data , 1 ,0);
		}
		else {
	 		alert(data);
		}
	}
	
	function rctmuprinter(data) {
		if (debug == 0) {
			external.RC('print' , data , 2 ,0);
		}
		else {
	 		alert(data);
		}
	}
	
	function rcprintfps(data) {
		if (debug == 0) {
			external.RC('printfps' , data , 1, 0);
		}
		else {
			alert(data);
		}
	}
	
	function rctest(data) {
		alert(external.RC('test' , data , 0 , 0));
	}
	
	// Shared Function
	function CurrencyFormatted(nStr)
	{
		nStr += '';
		x = nStr.split('.');
		x1 = x[0];
		x2 = x.length > 1 ? '.' + x[1] : '';
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + ',' + '$2');
		}
		return x1 + x2;
	}