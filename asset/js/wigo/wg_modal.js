
var modal_html = '<div class="modal hide fade wg_modal" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i class="icon-loading"></i></div><div class="modal-footer">&nbsp;</div></div>';

var modal_html = '<div class="modal hide fade wg_modal" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i>Loading ...</i></div></div>';

var alert_html = '<div class="modal hide fade wg_alert" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i class="icon-loading"></i><div class="wg_alert-msg"></div></div></div>';

//<i class="icon-loading"></i>

/**
 * wgm alert ===
 * display modal in alert style
 *
 */
function wgm_alert(msg, status) {
	$('body').append(alert_html);
	div = '.wg_alert';
	$(div).find('.wg_alert-msg').addClass(status);
	$(div).find('.wg_alert-msg').html(msg);
	$(div).modal('show');
	$(div).on('hidden', function () {
		$(div).remove();
    });
}

/**
 * wgm show ===
 * just showup a modal box
 *
 */
function wgm_show() {
	$('body').append(modal_html);
	div = '.wg_modal';
	$(div).modal('show');
	$(div).on('hidden', function () {
		$(div).remove();
    });
	return $(div);
}

function wgm_load(url, targetdiv) {
	targetdiv = '#'+targetdiv;
	$.post(url, {}, function(data){
		$(targetdiv).html(data);
		$(targetdiv).modal('show');
	},'html');
}


function wgm_open_modal(url, title, targetdiv) {
	//$('body').append(modal_html);
	//var obj = $('.wg_modal');
	var obj = $('.wg_modal');
	$.post(url, {}, function(data){
		$(obj).find('.modal-body').html(data);
		$(obj).find('.modal-title').html(title);
		$(obj).modal('show');
		//$(obj).on('hidden', function () { $(obj).remove(); });
	},'html');
}

function wgm_close(obj) {
	$(obj).closest('.wg_modal').find('.close').trigger('click');
}


function wgModal() {

	var modal_html1 = '<div class="modal hide fade wg_modal" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i>Loading ...</i></div><div class="modal-footer">&nbsp;</div></div>';

	var modal_html = '<div class="modal hide fade wg_modal" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i>Loading ...</i></div></div>';

	var alert_html = '<div class="modal hide fade wg_alert" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><div class="wg_alert-msg"></div></div></div>';


	this.alert = function(msg, status) {
		$('body').append(alert_html);
		div = '.wg_alert';
		$(div).find('.wg_alert-msg').addClass(status);
		$(div).find('.wg_alert-msg').html(msg);
		$(div).modal('show');
		$(div).on('hidden', function () {
			$(div).remove();
		});
	}

	this.debug = function() { this.alert('wgm loaded'); }

}



/***** DOCUMENT READIES ****/

$(document).ready(function(){

	$('.wgm_open_modal').live('click', function(e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var title = $(this).attr('title');
		wgm_open_modal(href, title);
	});

	$('.wgm-alert').live('click', function(e) {
		e.preventDefault();
		var wgm = new wgModal();
	});

	$('.wgm-ajax').live('click', function(e) {
		e.preventDefault();
		var wgm = new wgModal();
	});

	$('.wgm_close').live('click', function(e) {
		e.preventDefault();
		$(this).closest('.wg_modal').find('.close').trigger('click');
	});


});


/*** register plugin ***/
$.fn.wgmodal = function(data){
	return this.each(function() {
	});
};
