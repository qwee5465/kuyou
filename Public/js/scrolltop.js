$(function() {
    var scrollDiv = document.createElement('div');
    $("body").append("<div style='height:100px;clear:both;'></div>");
    $(scrollDiv).attr('id', 'toTop').html('^ 返回顶部').appendTo('body');
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('#toTop').fadeIn();
        } else {
            $('#toTop').fadeOut();
        }
    });
    $('#toTop').click(function() {
        $('body,html').animate({ scrollTop: 0 }, 800);
    });
});