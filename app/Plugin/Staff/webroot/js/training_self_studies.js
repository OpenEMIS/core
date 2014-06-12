 $(document).ready(function() {
    objTrainingSelfStudies.init();
});

var objTrainingSelfStudies = {

    init: function() {
        var elementSelfStudy = '#searchTrainingProvider';
        objTrainingSelfStudies.attachAutoComplete(elementSelfStudy, getRootURL() + $(elementSelfStudy).attr('url'), objTrainingSelfStudies.selectTrainingProviderField);
    },

   selectTrainingProviderField: function(event, ui) {
        var val = ui.item.value;
        var element;
        console.log(ui);
        for(var i in val) {
            element = $('.' + i);

            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchTrainingProvider') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },

    
    attachAutoComplete: function(element, url, callback) {
        $(element).autocomplete({
            source: url,
            minLength: 2,
            select: callback
        });
    },
  
    errorFlag: function() {
        var errorMsg = $('.custom-file-msg').length;
        if(errorMsg==0){
            return true;
        }else{
            return false;
        }
    }


}