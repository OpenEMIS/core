//Date and Time Picker v.1.0.0

//Fade out any datepicker/timepicker when it reached the top header when scrolling
var t ;
$( document ).on('DOMMouseScroll mousewheel scroll', function() {
    $('.dropdown-menu').each(function () {
        if (($(this).offset().top - $(window).scrollTop()) < 5) {
            $(this).stop().fadeOut(30);
        } else {
            $(this).stop().fadeIn(0);
            $(".time").click(function(){
                $(".bootstrap-timepicker-widget").css("display","inline-block", "important");
            });
        }
    });       
});