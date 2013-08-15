//$(document).ready(function(){
//
//	if(use_ajax == TRUE){
//
//		//AJAX is ON
//
//		$('#formSubmit').bind('submit', function(e) {
//			e.preventDefault();
//			$('#formSubmit').ajaxSubmit({
//				url: mod_url + 'ajax_submit_action',
//				success: function(data) {
//					console.log(data);
//					if (data.status == 'ok') {
//						location.reload(true);
//					} else {
//						alert(data.msg);
//					}
//				},
//				dataType: 'json'
//			});
//		});
//
//		//bind cancel button to close dialog
//		$('.cancel').bind('click', function(e) {
//			e.preventDefault();
//			$(this).closest('.ui-dialog-content').dialog('close');
//		});
//
//	} elseif(use_ajax == FALSE) {
//
//		//AJAX is OFF
//
//		$('.cancel').bind('click', function(e) {
//			e.preventDefault();
//			window.history.back();
//			console.log('asd');
//		});
//
//	}
//
//
//});

if(use_ajax == true){
	$(document).ready(function(){
		conlog('ajaxon');

		$('#formSubmit').bind('submit', function(e) {
			e.preventDefault();
			$('#formSubmit').ajaxSubmit({
				url: mod_url + 'ajax_submit_action',
				success: function(data) {
					console.log(data);
					if (data.status == 'ok') {
						location.reload(true);
					} else {
						alert(data.msg);
					}
				},
				dataType: 'json'
			});
		});

		//bind cancel button to close dialog
		$('.do_cancel').bind('click', function(e) {
			e.preventDefault();
			$(this).closest('.ui-dialog-content').dialog('close');
		});

	});
}

if(use_ajax == false){
	$(document).ready(function(){
		conlog('ajaxoff');

		$('.do_cancel').bind('click', function(e) {
			e.preventDefault();
			window.history.back();
		});
	});
}
