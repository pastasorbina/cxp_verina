$(document).ready(function () {

	$('.areyousure').click(function(e) {
		if (!confirm("Are you sure")) {
			e.preventDefault();
		}
	});

});
