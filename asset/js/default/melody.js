$(document).ready(function(){
	$("#lobar").hide();

	$('.openbox').bind('click', function(e) {
		e.preventDefault();
		var title = $(this).attr('title');
		var url = $(this).attr('href');
		$('#lobar').show();
		// Load the detail
		$.post(url , {}, function(data) {
			$( "#dbox" ).html(data);
			$( "#dbox" ).dialog({
				height: 500,
				width: 500,
				modal: true,
				title:title
			});
		} , 'html').complete(function() { $('#lobar').hide(); });
	});

	$('.insert_tube').each(function(){
		$('.tube_submit').live('click', function(e){
			//alert($(this).prev('.tube_id').val());
			//var ckeditor = $(this).prev('.cke_contents iframe html body').html();
			var ckeditor = $('.cke_contents iframe html body').html();
			//var ckeditor = $(this).prev('textarea').html();
			//CKEDITOR.editor.insertHtml('asd');
			//CKEDITOR.ELEMENT_MODE_APPENDTO('asd');
			var input = $(this).siblings('.tube_id').val();
			var head = '<iframe class="youtube-player" type="text/html" width="150" height="150" src="http://www.youtube.com/embed/';
			var tail = '?version=3&autohide=1&showinfo=0"" frameborder="0"></iframe>';
			var html = head+input+tail;
			var instance = $(this).attr('rel');
			CKEDITOR.instances[instance].insertText(html);
			console.log(CKEDITOR );
		});

	});

});


/**
 * Sidebar Menu
 */
$(document).ready(function(){
	$(".sbmenu ul").each(function() {
		if($(this).parent().attr('class') != 'active' ){
			$(this).hide();
		}
	});
	$(".sbmenu a.expander").each(function() {
		$(this).click(function(e) {
			e.preventDefault();
			$(this).siblings("ul").toggle(100);
		});
	});


});
