
function grid(){
	$('#mode').addClass('flip');
	$('#loop').fadeOut('fast',function(){
		grid_update();
		$(this).fadeIn('fast');
	});
}

function list(){
	$('#mode').removeClass('flip');
	$('#loop').fadeOut('fast',function(){
		list_update();
		$(this).fadeIn('fast');
	});
}

function grid_update(){
	$('#loop').addClass('grid').removeClass('list');
	$('#loop').find('.thumb img').attr({'width':'190','height':'190'});
	$('#loop').find('.post').mouseenter(function(){
		$(this).css('background-color','#FFEA97').find('.thumb').hide().css('z-index','-1');
	}).mouseleave(function(){
		$(this).css('background-color','#f5f5f5').find('.thumb').show().css('z-index','1');
	});
	$('#loop').find('.post').click(function(){
		location.href=$(this).find('h2 a').attr('href');
	});

	$.cookie('mode','grid');
}



$('#mode').toggle(function(){
	if($.cookie('mode')=='grid'){
		$.cookie('mode','list');
		list();
	} else {
		$.cookie('mode','grid');
		grid();
	}
}, function(){
	if($.cookie('mode')=='list'){
		$.cookie('mode','grid');
		grid();
	}else{
		$.cookie('mode','list');
		list();
	}
});
