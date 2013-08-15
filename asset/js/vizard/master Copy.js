	var default_setting = {
		width: 600,
		height: 400,
		handle_after_edit_load: function() {},
		handle_after_add: function() {}
	};

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
		document.frmSearch.page_number.value = 1;
		document.frmSearch.submit();
	}

	function nav_next() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.page_number.value) + 1;
		document.frmSearch.submit();
	}

	function nav_prev() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.page_number.value) - 1;
		document.frmSearch.submit();
	}

	function nav_first() {
		document.frmSearch.page_number.value = 1;
		document.frmSearch.submit();
	}

	function nav_last() {
		document.frmSearch.page_number.value = parseInt(document.frmSearch.total_page.value);
		document.frmSearch.submit();
	}

	function submit_form() {
		document.frmSearch.page_number.value = 1;
	}

	function load_select_ajax(el, module , postScript) {
		$.post(site_url + 'autocomplete/get/' + module + '/html', {d: 1} , function(data) {
			el.html(data);
			if (postScript) (postScript());
		});
	}

	$(document).ready(function() {

		$('.export_excel').click(function(e) {
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

		// Datepicker
		$(".datepicker").datepicker({
			dateFormat: 'yy-mm-dd'
		});
		$(".datetimepicker").datetimepicker({
			dateFormat: 'yy-mm-dd'
		});
		// Datepicker fix
		$('#ui-datepicker-div').css('z-index' , '2007');

		// Time Picker
		$(".clockpicker").clockpick({
			starthour: 6,
			endhour: 20,
			minutedivisions: 12,
			military: true
		});

	});
