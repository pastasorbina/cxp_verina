var default_setting = {
	width: 600,
	height: 400,
	position: 'center',
	handle_after_edit_load: function() {},
	handle_after_add: function() {}
};

//var base_class = '';
//var use_ajax;

if (setting == undefined) { var setting = default_setting; 	} else { setting = $.extend(default_setting , setting); }

$(document).ready( function() {

	$(".ajax_list_select").live('click', function(e){
		e.preventDefault();
		var href = $(this).attr('href');

		var inputid = $(this).attr('data-inputid');
		var labelid = $(this).attr('data-labelid');
		console.log(inputid);
		console.log(labelid);

		var target_dialog = "#dbox";
		var id = $(this).attr('id');
		var title = $(this).attr('title');
		var url = $(this).attr('href');

		var params = new Array();
		var height = $(this).attr('data-height');
		var width = $(this).attr('data-width');
		var position = $(this).attr('data-position');
		if(position) { setting.position = position; }
		if(width) { setting.width = width; }
		if(height) { setting.height = height; }

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
			$(target_dialog).data('href', url);
			$(target_dialog).data('inputid', inputid);
			$(target_dialog).data('labelid', labelid);
		} , 'html').complete(function() { $('#lobar').hide(); });

	});

	$(".ajax_list_select_none").live('click', function(e){
		e.preventDefault(); 
		var inputid = $(this).attr('data-inputid');
		var labelid = $(this).attr('data-labelid');
		var target_input = $(document).find('#'+inputid);
		if(!labelid){
			var target_label = $(document).find('#'+inputid+'_label');
		} else {
			var target_label = $(document).find('#'+labelid);
		}
		$(target_input).val('0');
		$(target_label).val('-none-');
		$(target_label).text('-none-');
	});



	// AJAX do selection
	$(".ajax_do_select").live('click', function(e){
		e.preventDefault();
		var dialog_obj = $(this).closest('.ui-dialog-content');
		var id = $(this).attr('data-id');
		var label = $(this).attr('data-label');
		var inputid = $(dialog_obj).data('inputid');
		var labelid = $(dialog_obj).data('labelid');

		$(dialog_obj).dialog('close');

		//alert(labelid);
		var target_input = $(document).find('#'+inputid);
		if(!labelid){
			var target_label = $(document).find('#'+inputid+'_label');
		} else {
			var target_label = $(document).find('#'+labelid);
		}

		$(target_input).val(id);
		$(target_input).trigger('change');
		$(target_label).val(label);
		$(target_label).text(label);
		$(target_label).trigger('change');

	});


	// AJAX paging
	$(".ajax_prev_page, .ajax_next_page").live('click', function(e){
		e.preventDefault();
		var paging_form_obj = $(this).closest('.ui-dialog-content').find('#ajax_filter_form');
		//$(this).closest('.ui-dialog-content').dialog('close');
		//console.log(paging_form_obj);
		var pagenum = $(this).attr('data-pagenum');
		$(paging_form_obj).find('#pagenum').val(pagenum);
		$(paging_form_obj).trigger('submit');
	});



	// AJAX paging form
	$('#ajax_filter_form').live('submit', function(e) {
		e.preventDefault();

		var dialog_obj = $(this).closest('.ui-dialog-content');
		var url = $(this).closest('.ui-dialog-content').data('href');
		$(this).ajaxSubmit({
			success: function(data) {
				if (data.status == 'ok') {
					console.log(data);
					var url = data.href;
					$.post(url , {ajax:true} , function(returndata) {
						$(dialog_obj).html(returndata);
					} , 'html').complete(function() {

					});
				} else {
					alert(data.msg);
				}
			},
			dataType: 'json'
		});
	});

	//AJAX do_selected
	$('.do_selected').live('click', function(e) {
		e.preventDefault();
		var action = $(this).attr('data-action');
		var form = $('#ajax_selected_do_form');
		var selected_action = $('#selected_action');
		var selected_id = $('.selected_id').val();
		$(selected_action).val(action);

		var answer = confirm("are you sure？");
		if (answer){
			start_loading();
			$(form).ajaxSubmit({
				success: function(ret) {
					if (ret.status == 'ok') {
						var card = get_card();
						reload_card(card.obj);
						debug_card();
					} else {
						alert(ret.msg);
					}
					finish_loading();
				},
				dataType: 'json'
			});
		}
	});
});


// ajax_list_select onCLick
/*
		$(".ajax_list_select").live('click', function(e){
			e.preventDefault();
			var href = $(this).attr('href');
			var old_index = get_curr_index();
			var new_index = add_card(old_index);

			var old_card = $('.card[data-index=' + old_index + ']');
			var new_card = $('.card[data-index=' + new_index + ']');

			var inputid = $(this).attr('data-inputid');
			var labelid = $(this).attr('data-labelid');

			$.post(href , {ajax:true, inputid:inputid , labelid:labelid} , function(data) {
				start_loading();
				$(new_card).html(data);
			} , 'html').complete(function() {
					old_card.slideUp('fast');
					$(new_card).attr('data-href', href);
					debug_card();
					finish_loading();
				});
		});

		// ajax_do_select onClick
		$(".ajax_do_select").live('click', function(e){
			e.preventDefault();
			var id = $(this).attr('data-id');
			var label = $(this).attr('data-label');
			var inputid = $(this).attr('data-inputid');
			var labelid = $(this).attr('data-labelid');
			var prev_card = slide_card_prev();
			var target_input = $(prev_card).find('#'+inputid);
			if(!labelid){
				var target_label = $(prev_card).find('#'+inputid+'_label');
			} else {
				var target_label = $(prev_card).find('#'+labelid);
			}

			$(target_input).val(id);
			$(target_input).trigger('change');
			$(target_label).text(label);
			$(target_label).trigger('change');
		});


		// AJAX paging
		$(".ajax_prev_page, .ajax_next_page").live('click', function(e){
			e.preventDefault();
			var paging_form_obj = $(this).closest('.card').find('#ajax_filter_form');
			//console.log(paging_form_obj);
			var pagenum = $(this).attr('data-pagenum');
			$(paging_form_obj).find('#pagenum').val(pagenum);
			$(paging_form_obj).trigger('submit');
		});
*/
