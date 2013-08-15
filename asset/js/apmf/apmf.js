var $ = jQuery.noConflict();
  $(window).load(function() {
    $('#mainslider.flexslider').flexslider({
          animation: "slide",
		  animationLoop: true,
		  directionNav: false,
    });
	    $('#secondslider.flexslider').flexslider({
          animation: "slide",
		  slideshow: true, 
    });
	
	$(function() {
		$('#activator').click(function(){
				$('#top_slide_content').animate({'top':'0px'},400);
		});
		$('#boxclose').click(function(){
				$('#top_slide_content').animate({'top':'-500px'},400);
		});
	
		
		
		$('.slides li').hover(function(){
					$(".speak-caption", this).stop().animate({top:'0px'},{queue:false,duration:300});
				}, function() {
					$(".speak-caption", this).stop().animate({top:'-170px'},{queue:false,duration:300});
				});	
	});
	
	$(".es-carousel ul li").hover(function(){
		 $(this).children('span').animate({left:"20px"},{queue:false,duration:300});
		 $(this).children('b').animate({top:"0px"},{queue:false,duration:300});
	}, function() {
         $(this).children('span').animate({left:"10px"},{queue:false,duration:300});
		 $(this).children('b').animate({top:"-20px"},{queue:false,duration:300});
	});
	

  });
  
  jQuery(function($){
	$(".tweet").tweet({
	  join_text: "auto",
	  username: "famousthemes",
	  count: 1,
	  auto_join_text_default: "we said,",
	  auto_join_text_ed: "we",
	  auto_join_text_ing: "we were",
	  auto_join_text_reply: "we replied",
	  auto_join_text_url: "we were checking out",
	  loading_text: "loading tweets..."
	});
  });
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}