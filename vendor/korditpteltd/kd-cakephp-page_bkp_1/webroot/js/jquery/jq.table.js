//Table Responsive and Shadow v.1.0.0

var TableResponsive = {
        init: function() {
            this.addScrollbarShadow('.table-responsive');
            this.addScrollbarShadow('.table-in-view');
            this.addScrollbarShadow('.table-scroll-y');
            this.tableOverflow();
        },

        //table shadow appears on scroll
        addScrollbarShadow: function(selector) {
            $(selector).each(function(index) {
                // console.log('index = ' + index);
                TableResponsive.horzScrollbarDetect(selector, index);
                $(this).on('scroll', function() {
                    TableResponsive.horzScrollbarDetect(selector, index);
                });
            });

            $(window).resize(function() {
                $(selector).each(function(index) {
                    TableResponsive.horzScrollbarDetect(selector, index);
                });
            });
        },

        horzScrollbarDetect: function(selector, index) {
            var $scrollable = $(selector);
            if ($scrollable.get(index)) {
                // var bodyDir = getComputedStyle(document.body).direction;

                var element = $scrollable.get(index);
                var totalWidth = element.scrollWidth;
                var leftPosition = Math.abs(element.scrollLeft);
                var viewWidth = element.offsetWidth;

                var rightPosition = totalWidth-(leftPosition+viewWidth);
                // var leftShadow = (bodyDir == 'ltr') ? 'shadow-left' : 'shadow-right';
                // var rightShadow = (bodyDir == 'ltr') ? 'shadow-right' : 'shadow-left';
                var leftShadow = 'shadow-left';
                var rightShadow =  'shadow-right';

                $wrapper = $(selector).eq(index).parent();

                if (rightPosition > 0) {
                    $wrapper.addClass(rightShadow); 
                } else {
                    $wrapper.removeClass(rightShadow);
                }
                if (leftPosition > 0) {
                    $wrapper.addClass(leftShadow);  
                } else {
                    $wrapper.removeClass(leftShadow);
                }
            }
        },

    tableOverflow: function() {
        $('.table-responsive').on('show.bs.dropdown', function () {
            $('.table-responsive').css( "overflow-x", "inherit" );
        });

        $('.table-responsive').on('hide.bs.dropdown', function () {
            $('.table-responsive').css( "overflow-x", "auto" );
        })
    }
};