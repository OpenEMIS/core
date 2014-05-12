// JavaScript Document
$(function() {
    Extracurricular.attachAutoComplete();
});


var Extracurricular = {
    attachAutoComplete: function() {
        var url = getRootURL() + $(".autoComplete").attr('url');
        // advanced search
        $(".autoComplete").autocomplete({
            source: url,
            minLength: 2,
            select: function(event, ui) {
                $('.autoComplete').val(ui.item.label);
                return false;
            }
        });
    },
}