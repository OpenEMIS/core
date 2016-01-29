//Header v.1.0.0

//Header will wrap with ellipsis
var Header = {
    init: function() {
        this.headerEllipsis();
    },

    headerEllipsis: function() {
        var headerWidthNow = document.getElementById('main-header').clientWidth;

        //If the width of the header is bigger than 340
        if (headerWidthNow > 340) {
            //On Hover
            $(".page-header h2").hover(
                function(e) {
                    var toolbarElements = $(".toolbar");
                    var headerToolbar = $(".page-header h2:hover + .toolbar");

                    headerToolbar.css('display', 'none');
                    toolbarElements.css('display', 'none');
                    $(this).css('max-width', '100%').css('white-space', 'normal').css('text-overflow', 'unset').css('display', 'inline-block');
                }, // mouseover
                function(e) {
                    var toolbarElements = $(".toolbar");

                    toolbarElements.css('display', 'inline-block');
                    $(this).css('max-width', '350px').css('white-space', 'nowrap').css('text-overflow', 'ellipsis').css('display', 'inline-block');

                } // mouseout
            );
        };
    }
};
