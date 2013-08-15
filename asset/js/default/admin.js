/* SHOW / HIDE LOADING BAR */

function show_loading(target) {
	if (target == undefined) { target = '#loadingbar'; }
	$(target).fadeIn();
}

function hide_loading(target) {
	if (target == undefined) { target = '#loadingbar'; }
	$(target).fadeOut();
}


/* PUSH CONFIRM MESSAGE */
function render_confirm() {
	confirmbox = $('#floating_confirm_box');
	if(confirmbox.size() < 1) {
		$('body').append('<div id="floating_confirm_box" class="hide"><div class="inner"></div></div>');
	}
	return confirmbox;
}

function push_confirm(type, msg) {
	if (msg == undefined) { msg = "Operation Successful"; }
	confirmbox = render_confirm();
	$(confirmbox).children('.inner').html(msg);
	$(confirmbox).fadeIn(400).delay(1500).fadeOut(400);
}

$(function(){ render_confirm(); })

