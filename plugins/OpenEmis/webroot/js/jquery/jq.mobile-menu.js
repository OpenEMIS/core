//Mobile Menu v.1.0.2

//On Mobile View -- Hamburgar Menu will appear
var MobileMenu = {
    init: function() {
        
        //Mobile: On window resize 
        $(window).resize(function() {
            MobileMenu.onClickMenu();
        });

        this.onClickMenu();
    },

    //Mobile: When the menu handler and content overlay is click
    onClickMenu: function() {
        var width = $(window).width();
        var bodyDir = getComputedStyle(document.body).direction;
        var menuHandler = $('.menu-handler');
        var rightPane = $('.right-pane');
        var leftPane = $('.left-pane');
        var contentOverlay = $('.content-overlay');
        
        menuHandler.off('click');
        rightPane.off('click');
        // console.log("width = " + width );
        if (width <= 1024) {
            leftPane.addClass("enable-overflow").css('opacity', '1');
            menuHandler.on('click', function() {
                leftPane.toggleClass("enable-overflow").css("display", "block", "!important");
                // console.log('click');
                if (bodyDir == 'ltr') {
                    rightPane.toggleClass("push-content-right").toggleClass("content-overlay");
                } else {
                    rightPane.toggleClass("push-content-left").toggleClass("content-overlay");
                }
            });

            rightPane.on('click', function() {
                rightPane.removeClass("push-content-right").removeClass("content-overlay");
                rightPane.removeClass("push-content-left").removeClass("content-overlay");
                leftPane.addClass("enable-overflow");
            });
        } else {

            return;
        }
    }
}
