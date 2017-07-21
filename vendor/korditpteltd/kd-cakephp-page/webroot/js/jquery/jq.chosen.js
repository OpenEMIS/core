//Multi Select Input v.1.0.0

var Chosen = {
    init: function() {
        if ($('.chosen-select').length > 0) {
            $('.chosen-select').chosen({
                allow_single_deselect: true
            });
        }

        //Resize the chosen on window resize
        $(window)
            .off('resize.chosen')
            .on('resize.chosen', function() {
                $('.chosen-select').each(function() {
                    var $this = $(this);
                    $this.next().css({
                        'width': $this.parent().width()
                    });
                })
            }).trigger('resize.chosen');
    }
};
