var ckeditor_default_height = 300;
var ckeditor_default_width = 800;

var config = new Array();
config['default'] = { toolbar: 'Editor' };
config['basic'] = { toolbar: 'Basic' };

function init_ckeditor(namespace, config_name) {
	var fname = $(namespace).attr('name');
	if (CKEDITOR.instances[fname]) {
		CKEDITOR.instances[fname].destroy(true);
	}
	$(namespace).ckeditor(config[config_name]);
}

$(function(){

	/**
	 * integrate ckfinder to ckeditor
	 */
	CKFinder.setupCKEditor( null, { basePath : asset_url+'ckfinder/', rememberLastFolder : false } ) ;


	/**
	 * initialize ckeditor
	 */
	$( 'textarea.ckeditor' ).each(function(e) {
		var height = $(this).attr('height');
		var width = $(this).attr('width');
		if(!height) { height = ckeditor_default_height; }
		if(!width) { width = ckeditor_default_width; }

		var fname = $(this).attr('name');
		if (CKEDITOR.instances[fname]) {
			CKEDITOR.instances[fname].destroy(true);
		}

		$(this).ckeditor({
			height: height, width: width, toolbar: 'Editor'
		}, function(){
			//calback here
		});
	});


	$( 'textarea.ckeditor_basic' ).each( function(e) {

		// setup height and width
		var height = $(this).attr('height');
		var width = $(this).attr('width');
		if(!height) { height = ckeditor_default_height; }
		if(!width) { width = ckeditor_default_width; }

		var fname = $(this).attr('name');
		if (CKEDITOR.instances[fname]) {
			CKEDITOR.instances[fname].destroy(true);
		}

		$(this).ckeditor({
			height: height, width: width, toolbar: 'Basic',
		});
	});



});
