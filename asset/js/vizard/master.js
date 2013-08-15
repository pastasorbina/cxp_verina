	var default_setting = {
		width: 600,
		height: 400,
		handle_after_edit_load: function() {},
		handle_after_add: function() {}
	};

	var base_class = '';
	var use_ajax;


	if (setting == undefined) {
		var setting = default_setting;
	}
	else {
		setting = $.extend(default_setting , setting);
	}

	function doSort(sortkey) {
		if (document.frmSearch.orderby.value == sortkey) {
			// Change direction
			document.frmSearch.ascdesc.value = (document.frmSearch.ascdesc.value == 'ASC') ? 'DESC' : 'ASC';
		}
		else {
			// Change sort key
			document.frmSearch.ascdesc.value = 'ASC';
			document.frmSearch.orderby.value = sortkey;
		}
		document.frmSearch.offset.value = 0;
		document.frmSearch.submit();
	}


	/* @deprecated */
	function nav_next() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.page_number.value) + 1;
		document.frmSearch.submit();
	}
	/* @deprecated */
	function nav_prev() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.page_number.value) - 1;
		document.frmSearch.submit();
	}
	/* @deprecated */
	function nav_first() {
		document.frmSearch.page_number.value = 1;
		document.frmSearch.submit();
	}
	/* @deprecated */
	function nav_last() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.total_page.value);
		document.frmSearch.submit();
	}
	/* @deprecated */
	function submit_form() {
		document.frmSearch.offset.value = 1;
	}



	function load_select_ajax(el, module , postScript) {
		$.post(site_url + 'autocomplete/get/' + module + '/html', {d: 1} , function(data) {
			el.html(data);
			if (postScript) (postScript());
		});
	}

	$(document).ready(function() {

		/* make sure when form search is submitted, offset is returned back to 0 @will */
		$(document.frmSearch).bind('submit', function(e) {
			e.preventDefault();
			document.frmSearch.offset.value = 0;
			this.submit();
		});

		$('.export_excel').live('click', function(e) {
			e.preventDefault();
			document.location = site_url + base_class + '/' + 'export_excel';
		});

		$('.add_entry').click(function(e) {
			e.preventDefault();
			$('#form1').resetForm();
			$('#form_add_edit').dialog('option' , 'title' , 'Add ' + base_title);
			$('#form_method').val('add');
			$('#form_add_edit').dialog('open');
			setting.handle_after_add();
		});

		$('.edit_entry').click(function(e) {
			e.preventDefault();
			var id = $(this).attr('id');
			$('#form_add_edit').dialog('option' , 'title' , 'Edit ' + base_title);
			$('#form_method').val('edit');
			$('#current_id').val(id);

			$('#lobar').show();

			// Load the detail
			$.post(site_url + base_class + '/load_edit' , {id : id} , function(data) {
				if (data._status == 'ok') {
					for (row in data) {
						$('#' + row).val(data[row]);
					}
					// Handle
					setting.handle_after_edit_load(id);
					$('#form_add_edit').dialog('open');
				}
				else {
					alert(data._msg);
				}
			} , 'json').complete(function() { $('#lobar').hide(); });
		});

		$('#delete_entry').click(function(e) {
			e.preventDefault();
			if(confirm("Are you sure?")) {
				$('#form_del_action').val('delete');
				$('#form_del').submit();
			}
		});

		$('#suspend_entry').click(function(e) {
			e.preventDefault();
			$('#form_del_action').val('suspend');
			$('#form_del').submit();
		});

		$('#suspend_entry').click(function(e) {
			e.preventDefault();
			$('#form_del_action').val('suspend');
			$('#form_del').submit();
		});

		//fix for style (change status to disable)
		$('#disable_entry').click(function(e) {
			e.preventDefault();
			$('#form_del_action').val('disable');
			$('#form_del').submit();
		});
		//fix for style (change status to enabled)
		$('#enable_entry').click(function(e) {
			e.preventDefault();
			$('#form_del_action').val('enable');
			$('#form_del').submit();
		});

		$('#activate_entry').click(function(e) {
			e.preventDefault();
			$('#form_del_action').val('activate');
			$('#form_del').submit();
		});

		$("#form_add_edit").dialog({
			width: setting.width,
			height: setting.height,
			autoOpen: false,
			modal: true,
			buttons: {
				Cancel: function() {
					$(this).dialog('close');
				},
				'Save': function() {
					$('#form1').submit();
				}
			}
		});

		$('#form1').submit(function(e) {
			e.preventDefault();
			$('#form1').ajaxSubmit({
				success: function(data ) {
					if (data.status == 'ok') {
						location.reload(true);
					}
					else {
						alert(data.msg);
					}
				},
				dataType: 'json'
			});
		});

		$('#check_all').click(function(e) {
			if ($(this).attr('checked') == true) {
				$('.entry_check').attr('checked' , true);
			}
			else {
				$('.entry_check').attr('checked' , false);
			}
		});

		$('#per_page_select').change(function(e) {
			document.frmSearch.per_page.value = $(this).val();
			document.frmSearch.submit();
		});







		/**
		 * open modal dialog box
		 * @will
		 */
		$('.dview').live('click',function(e) {
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


		/**
		 * if Use AJAX, load ajax functions
		 * @will
		 */
		if( use_ajax ) {
			//bind add and edit button
			$('a.add, button.add').each(function(a) {
				var url = $(this).attr('href');
				var new_url = url.replace("/add", "/ajax_add");
				$(this).attr('href', new_url);
				$(this).addClass("dview");
			});

			$('a.edit, button.edit').each(function(a) {
				var url = $(this).attr('href');
				var new_url = url.replace("/edit", "/ajax_edit");
				$(this).attr('href', new_url);
				$(this).addClass("dview")
			});


			//$('#formSubmit').bind('keypress', function(e) { //13 = 'enter'
			//	if(e.which == 13) {
			//		$(this).submit();
			//	}
			//});

			//$('#formSubmit').bind('submit', function(e) {
			//	e.preventDefault();
			//	$('#formSubmit').ajaxSubmit({
			//		url: mod_url + 'ajax_submit_action',
			//		success: function(data) {
			//			if (data.status == 'ok') {
			//				location.reload(true);
			//			} else {
			//				alert(data.msg);
			//			}
			//		},
			//		dataType: 'json'
			//	});
			//});
			//
			////bind cancel button to close dialog
			//$('.cancel').one('click', function(e) {
			//	e.preventDefault();
			//	$(this).closest('.ui-dialog-content').dialog('close');
			//});
		}

	});
