	var default_setting = {
		width: 600,
		height: 400,
		position: 'top',
		ask_confirm: 'no',
		handle_after_edit_load: function() {},
		handle_after_add: function() {}
	};

	var params = new Array();

	if (setting == undefined) {
		var setting = default_setting;
	}
	else {
		setting = $.extend(default_setting , setting);
	}


	function set_size(w,h) {
		setting.width=w?w:setting.width;
		setting.height=h?h:setting.height
	}


	function do_confirm(url) {
		var answer = confirm("あなたはよろしいですか？");
			if (answer){
				window.location = url;
			}
	}

	function trim(str){
		return str.replace(/^\s+|\s+$/g,'');
	}




$(document).ready(function() {


	$('.select_category').click(function(e) {
		e.preventDefault();
		var id = $(this).attr('id');
		$("#dbox").dialog( "close" );
	});

	$('.xxxdview').click(function(e) {
		e.preventDefault();

		var target_dialog = "#dbox";
		var id = $(this).attr('id');
		var title = $(this).attr('title');
		var url = $(this).attr('href');

		var params_string = $.trim($(this).attr('rel'));
		var params_arr = params_string.split(";");
		var params = new Array();
		for( var i=0; i<params_arr.length; i++) {
			var params_arr2 = $.trim(params_arr[i]).split("=");
			var params_key = $.trim(params_arr2[0]);
			var params_value = $.trim(params_arr2[1]);
			params[params_key] = params_value;
		}
		console.log(params['target']);
		if(params['dialog']) { target_dialog = params['dialog']; }
		if(params['width']) { setting.width = params['width']; }
		if(params['height']) { setting.height = params['height']; }
		if(params['position']) { setting.position = params['position']; }

		$.params = params;

		//open dialog
		$('#lobar').show();
		$.post(url , {id : id} , function(data) {
			$( target_dialog ).html(data);
			$( target_dialog ).dialog({
				height: setting.height,
				width: setting.width,
				position: setting.position,
				modal: true,
				title: title
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
