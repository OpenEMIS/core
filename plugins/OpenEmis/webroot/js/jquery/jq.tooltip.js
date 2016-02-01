//Tooltip v.1.0.1

var Tooltip = {
    init: function() {
        this.tooltipPosition();
        this.enableTooltip();
        this.tooltipColored();
    },

    enableTooltip: function() {
        $('[data-toggle="tooltip"]').tooltip();
        $('.focus').focus();

        $(window).on("touchstart", function(ev) {
            var e = ev.originalEvent;
            console.log(e.touches);

            var tableTooltip = $('.table-tooltip');
            var inputButton = $('.input [data-toggle="tooltip"]');

            tableTooltip.tooltip('hide');
            inputButton.tooltip('hide');
        });
    },

    tooltipPosition: function() {
        var bodyDir = getComputedStyle(document.body).direction;

        if (bodyDir == 'ltr') {
            $('.table-tooltip').tooltip({
                placement: "right"
            });
        } else {
            $('.table-tooltip').tooltip({
                placement: "left"
            });
        }
    },

    tooltipColored: function() {
        var buttonRed = $('.icon-red[data-toggle="tooltip"]');
        var buttonBlue = $('.icon-blue[data-toggle="tooltip"]');
        var buttonGreen = $('.icon-green[data-toggle="tooltip"]');
        var buttonOrange = $('.icon-orange[data-toggle="tooltip"]');

        //If Button Red Color, Tooltip Change to Red
        buttonRed.hover(function() {
            var tooltipOpacity = $('.tooltip');
            var tooltipArrow = $('.tooltip-arrow');
            var tooltipInner = $('.tooltip-inner');
            var bodyDir = getComputedStyle(document.body).direction;

            //Tooltip Opacity
            tooltipOpacity.css('opacity', '1');

            //LTR & RTL different border color
            if (bodyDir == 'ltr') {
                tooltipArrow.css('border-right-color', '#CC5C5C');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#CC5C5C').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'left').css('padding', '15px');
            } else {
                tooltipArrow.css('border-left-color', '#CC5C5C');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#CC5C5C').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'right').css('padding', '15px');
            }
        });

        //If Button Blue Color, Tooltip Change to Blue
        buttonBlue.hover(function() {
            var tooltipOpacity = $('.tooltip');
            var tooltipArrow = $('.tooltip-arrow');
            var tooltipInner = $('.tooltip-inner');
            var bodyDir = getComputedStyle(document.body).direction;

            //Tooltip Opacity
            tooltipOpacity.css('opacity', '1');

            //LTR & RTL different border color
            if (bodyDir == 'ltr') {
                tooltipArrow.css('border-right-color', '#5C82CC');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#5C82CC').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'left').css('padding', '15px');
            } else {
                tooltipArrow.css('border-left-color', '#5C82CC');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#5C82CC').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'right').css('padding', '15px');
            }
        });

        //If Button Green Color, Tooltip Change to Green
        buttonGreen.hover(function() {
            var tooltipOpacity = $('.tooltip');
            var tooltipArrow = $('.tooltip-arrow');
            var tooltipInner = $('.tooltip-inner');
            var bodyDir = getComputedStyle(document.body).direction;

            //Tooltip Opacity
            tooltipOpacity.css('opacity', '1');

            //LTR & RTL different border color
            if (bodyDir == 'ltr') {
                tooltipArrow.css('border-right-color', '#77B576');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#77B576').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'left').css('padding', '15px');
            } else {
                tooltipArrow.css('border-left-color', '#77B576');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#77B576').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'right').css('padding', '15px');
            }
        });

        //If Button Orange Color, Tooltip Change to Orange
        buttonOrange.hover(function() {
            var tooltipOpacity = $('.tooltip');
            var tooltipArrow = $('.tooltip-arrow');
            var tooltipInner = $('.tooltip-inner');
            var bodyDir = getComputedStyle(document.body).direction;

            //Tooltip Opacity
            tooltipOpacity.css('opacity', '1');

            //LTR & RTL different border color
            if (bodyDir == 'ltr') {
                tooltipArrow.css('border-right-color', '#E6BA64');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#E6BA64').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'left').css('padding', '15px');
            } else {
                tooltipArrow.css('border-left-color', '#E6BA64');
                //Tooltip Inner Container Color
                tooltipInner.css('background-color', '#E6BA64').css('box-shadow', '0 2px 3px rgba(0,0,0,0.5)').css('text-align', 'right').css('padding', '15px');
            }
        });
    }

};
