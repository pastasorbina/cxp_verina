	var default_setting = {
		width: 600,
		height: 400,
		ask_confirm: 'no',
		handle_after_edit_load: function() {},
		handle_after_add: function() {},


	};

	if (setting == undefined) {
		var setting = default_setting;
	}
	else {
		setting = $.extend(default_setting , setting);
	}

	//$(".view_dialog").dialog({
	//	width: setting.width,
	//	height: setting.height,
	//	autoOpen: false,
	//	modal: true,
	//	buttons: {
	//		Cancel: function() {
	//			$(this).dialog('close');
	//		},
	//		'Save': function() {
	//			$('#form1').submit();
	//		}
	//	}
	//});

	function set_size(w,h) {
		setting.width=w?w:setting.width;
		setting.height=h?h:setting.height
	}

	function ask_confirm(w) {
		setting.ask_confirm=w?w:setting.ask_confirm;
	}

	function do_confirm(url) {
		var answer = confirm("are you sure ?");
			if (answer){
				window.location = url;
			}
	}



$(document).ready(function() {
//document.ready-START

	$('.select_category').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('id');
		$("#dbox").dialog( "close" );
	});

	$('.dview').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('id');
		var title = $(this).attr('title');
		var url = $(this).attr('rel');
		$('#lobar').show();
		// Load the detail
		$.post(url , {id : id} , function(data) {
			$( "#dbox" ).html(data);
			$( "#dbox" ).dialog({
				height: setting.height,
				width: setting.width,
				modal: true,
				title:title
				//buttons: {
				//	"Close": function() {
				//		$( this ).dialog( "close" );
				//	}
				//}
			});
		} , 'html').complete(function() { $('#lobar').hide(); });
 	});

	$('.doact').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('id');
		var url = $(this).attr('rel');

		myBlock: {
			if(setting.ask_confirm == 'no') {
				var answer = confirm("Are you sure ?");
				if (!answer){
					break myBlock;
				}
			}
			$('#lobar').show();
			// Load the detail
			$.post(url , {id : id} , function(data) {
				var string = '<div class="confirm_' + data._status + '"><span>' + data._msg + '</span></div>';
				$('#conbar').html(string);
			} , 'json')
			.complete(function() {
					$('#lobar').hide();
					data._complete;
				});

		};

	});


	$('.returnvalue').click(function(e) {
		e.preventDefault();
		var name = $(this).attr('name');
		var title = $(this).attr('title');
		var value = $(this).attr('rel');
		$( "#" + name ).value(value);
		$( "#dbox" ).close(); 
 	});

//document.ready-END
});

function throwvalue( id_target, id_value, label_target, label_value ) {
	$( "#dbox" ).dialog( "close" );
	$( "#" + id_target ).val( id_value );
	$( "#" + label_target ).val( label_value );
}
