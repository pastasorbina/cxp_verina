function count_down_menu(div) {
    $(div).each(function() {
        var obj = $(this); 
        var d = $(obj).find('.day');
        var h = $(obj).find('.hour');
        var m = $(obj).find('.minute');
        var s = $(obj).find('.second');
         
 
        day = parseFloat($(d).text());
        hour = parseFloat($(h).text());
        minute = parseFloat($(m).text());
        second = parseFloat($(s).text());
 
        second--;
        if(second < 0) { second = 59; minute = minute - 1; }
        if(minute < 0) { minute = 59; hour = hour - 1; }
        if(hour < 0) { hour = 23; day = day - 1; }
        $(d).html(day);
        $(h).html(hour);
        $(m).html(minute);
        $(s).html(second);
        //$(s).html("0".substring(second >= 10) + second);
    });
}