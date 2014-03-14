// JavaScript Document
$(document).ready(function() {
});
var rubricsTemplate = {
    addHeader: function(id) {
        //  alert(getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/header');
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/header',
            data: {id: id, last_id: $('#last_id').val()},
            dataType: 'json',
            success: function(data) {
                // alert(data['html']);
                var alertOpt = {
                    parent: 'form',
                    title: data['message'],
                    text: data['message'],
                    type: alertType.info, // alertType.info or alertType.warn or alertType.ok
                    //position: 'absolute',
                     css: {top:'220px', left:'43%'}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true,
                }

                $.alert(alertOpt);

                $('form .table_view').append(data['html']);
                $('#last_id').val(parseInt($('#last_id').val()) + 1);
            }
        });
    },
    addRow: function(id) {
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/criteria',
            data: {id: id, last_id: $('#last_id').val()},
            dataType: 'json',
            success: function(data) {
                //  alert(data);
                var alertOpt = {
                    parent: 'form',
                    title: data['message'],
                    text: data['message'],
                    type: alertType.info, // alertType.info or alertType.warn or alertType.ok
                   // position: 'center',
                    css: {top:'220px', left:'43%'}, // positioning of your alert, or other css property like width, eg. {top: '-10px', left: '-20px'}
                    autoFadeOut: true,
                }

                $.alert(alertOpt);
                
                $('form .table_view').append(data['html']);
                $('#last_id').val(parseInt($('#last_id').val()) + 1);
            }
        });
    },
    initHeader: function(id) {
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/header',
            data: {id: id, last_id: 0},
            dataType: 'json',
            success: function(data) {
                // alert(data);
                $('form .table_view').append(data['html']);
                $('#last_id').val(parseInt($('#last_id').val()) + 1);
            }
        });
    },
    initRow: function(id) {
        $.ajax({
            type: "POST",
            url: getRootURL() + 'Quality/rubricsTemplatesSubheaderAjaxAddRow/criteria',
            data: {id: id, last_id: 1},
            dataType: 'json',
            success: function(data) {
                //  alert(data);
                $('form .table_view').append(data['html']);
                $('#last_id').val(parseInt($('#last_id').val()) + 1);
            }
        });
    }
};