
var modal_html = '<div class="modal hide fade wg_modal" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><i>Loading ...</i></div><div class="modal-footer">&nbsp;</div></div>';

var alert_html = '<div class="modal hide fade wg_alert" ><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3 class="modal-title">&nbsp;</h3></div><div class="modal-body"><div class="wg_alert-msg"></div></div></div>';


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


function wgm_open_modal(url, targetdiv) {
	targetdiv = '#'+targetdiv;
	$.post(url, {}, function(data){
		$(targetdiv).html(data);
		$(targetdiv).modal('show');
	},'html');
}



/***** DOCUMENT READIES ****/

$(document).ready(function(){

	$('.wgm-dialog').live('click', function() {

	});

});


/*** register plugin ***/
(function($){
	$.fn.wgm = function(data){
		console.log(data);
	};
})(jQuery);
