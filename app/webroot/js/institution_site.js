$(document).ready(function() {
	objInstitutionSite.addAreaSwitching();
});

var objInstitutionSite = {
    addAreaSwitching : function(){
        
        $('select[name*="[area_level_"]').each(function(i,obj){
           
            $(obj).change(function(o){
                var TotalAreaLevel = $('select[name*="[area_level_"]').length;
				
                var currentSelect = $(this).attr('name').replace('data[InstitutionSite][area_level_','');
				
                currentSelect = currentSelect.replace(']','');
                currentSelect = parseInt(currentSelect);
				
                for(i=currentSelect+1;i<TotalAreaLevel;i++){
                    //console.log($('select[name=data\\[InstitutionSite\\]\\[area_level_'+i+'\\]]'));
                    $('select[name=data\\[InstitutionSite\\]\\[area_level_'+i+'\\]]').find('option').remove();
                }
                objInstitutionSite.fetchChildren(this);
                
            });
            
        });
    },
	
    fetchChildren :function (currentobj){
        var selected = $(currentobj).val();
        var maskId;
        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: getRootURL()+'/InstitutionSites/viewAreaChildren/'+selected,
            beforeSend: function (jqXHR) {
				maskId = $.mask({text:i18n.General.textLoadAreas});
            },
            success: function (data, textStatus) {
				//console.log(data)
			
				var callback = function(data) {
						tpl = '';
						$.each(data,function(i,o){
							//console.log(o)
							tpl += '<option value="'+i+'">'+data[i]+'</option>';
						})
						var nextselect = $(currentobj).parent().parent().next().find('select');
						//console.log(nextselect)
						nextselect.find('option').remove();
						nextselect.append(tpl);
						
				};
				$.unmask({ id: maskId,callback: callback(data)});
            }
        })
    },
	
	getGradeList: function(obj) {
		var programmeId = $(obj).val();
		var exclude = [];
		$('.grades').each(function() {
			exclude.push($(this).val());
		});
		var maskId;
		var url = getRootURL() + $(obj).attr('url');
		var ajaxParams = {programmeId: programmeId, exclude: exclude};
		var ajaxSuccess = function(data, textStatus) {
			var callback = function() {
				$(obj).closest('.table_row').find('.grades').html(data);
			};
			$.unmask({id: maskId, callback: callback});
		};
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: ajaxParams,
			beforeSend: function (jqXHR) { maskId = $.mask({parent: '.content_wrapper'}); },
			success: ajaxSuccess
		});
	}
}