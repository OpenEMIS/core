dataStorage['institutionSite'] = {};

$(document).ready(function() {
	$('#site').each(function() {
		var id = '#' + $(this).attr('id');
		$(this).find('.link-edit').click(function() { objInstitutionSite.editDetails(id); });
		//$(this).find('input.btn_save').click(function() { objInstitutionSite.saveDetails(id); });
		$(this).find('input.btn_cancel').click(function() { jsForm.cancelEdit(id); $(id + ' .controls').hide(); $('#googlemap').show();});
	});
	objInstitutionSite.addAreaSwitching();
	
});


var objInstitutionSite = {
        
	saveDetails: function(id) {
		var bool = true;
		var errors = '';
		$('[req]').each(function(i,o){
			//console.log($(o).val());
//			console.log($(this));
			if($(this).find('input, select, textarea').val() == "0" || $(this).find('input, select, textarea').val().trim() == "" ){
				errors += $(this).attr('req')+"\n";
				bool = false;
			}
		});
		
		
		var gotSelVal = 0
		$('select[name*="[area_level_"]').each(function(){
//			console.log($(this).val())
			if($(this).val() != 0){
				gotSelVal = 1;
			}
		})
		if(gotSelVal == 0){
			errors += i18n.InstitutionSites.textArea;
			bool = false;
		}
		

		if(bool){
			//$.mask({text: i18n.General.textSaving});
		}else{
			alert(i18n.General.textRequiredField+(errors.length >1?"s":"")+": \n"+errors);
			return bool;
		}
                
		var maskId;
		var url = $(id + ' form').attr('action');
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: url,
			data: $(id + ' form').serialize(),
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: id, text: i18n.General.textSaving});
			},
			success: function (data, textStatus) {
				var callback = function() {
					jsForm.doneEdit(id);
					$(id+ ' .controls').hide();
				};
				$.unmask({id: maskId, callback: callback});
                                $('#googlemap').show();
                                //window.location = getRootURL()+'InstitutionSites/view/';
			}
		});
	},
	
    editDetails: function(id) {
		var maskId = id + '-mask';
		var provider, status;
		var url = $(id + ' form').attr('action');
		var key = 'institutionSite';
		$('#googlemap').hide();
		if($(id + ' .controls:visible').length==0 && $(maskId).length==0) {
			if(dataStorage[key]['edit'] == undefined) {
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: url,
					beforeSend: function (jqXHR) {
						$.mask({id: maskId, parent: id});
					},
					success: function (data, textStatus) {
						dataStorage[key]['edit'] = {};
						var callback = function() {
							dataStorage[key]['edit'] = data;
							jsForm.edit(id, data);
                            objInstitutionSite.addAreaSwitching()
							$(id + ' .controls').removeClass('none');
						};
						$.unmask({id: maskId, callback: callback});
					}
				});
			} else {
				var colInfo = dataStorage[key]['edit'];
				jsForm.edit(id, colInfo);
                objInstitutionSite.addAreaSwitching();
				$(id + ' .controls').addClass('none');
			}
		}
                
                /*options = { serviceUrl:'http://216.12.214.10/korditutils/tmp/alee/testac.php' };
                a = $('#area_id').autocomplete(options);
                */
   
                
	},
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
    }
    
    
}