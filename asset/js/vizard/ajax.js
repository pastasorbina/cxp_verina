
$(document).ready(function() {

	$('#formSubmit').bind('keypress', function(e) { //13 = 'enter'
		if(e.which == 13) {
			$(this).submit();
		}
	});

	$('#formSubmit').bind('submit', function(e) {
		e.preventDefault();
			$('#formSubmit').ajaxSubmit({
				url: mod_url + 'ajax_submit_action',
				success: function(data) {
					if (data.status == 'ok') {
						location.reload(true);
					} else {
						alert(data.msg);

						//$.post(current_uri , { } , function(data) {
						//	//$( target_dialog ).html(data);
						//	$(this).closest('.ui-dialog-content').html(data);
						//	//$( target_dialog ).dialog({
						//	//	height: setting.height,
						//	//	width: setting.width,
						//	//	position: setting.position,
						//	//	modal: true,
						//	//	title: title
						//	//});
						//} , 'html').complete(function() { $('#lobar').hide(); });
					}
				},
				dataType: 'json'
			});


	});

	$('.cancel').one('click', function(e) {
		e.preventDefault();
		$(this).closest('.ui-dialog-content').dialog('close');
	});

});
