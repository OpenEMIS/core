/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

$(document).ready(function() {
	objInstitution.init();
});

var objInstitution = {	
	init :function(){
		objInstitution.addAreaSwitching();
	},
    addSite: function() {
		var bool = true;
		var errors = '';
		$('[req]').each(function(i,o){
			//console.log($(o).val());
			if($(this).val() == "0" || $(o).val().trim() == "" ){
				errors += $(this).attr('req')+"\n";
				bool = false;
			}
		});
		var gotSelVal = 0
		/*$('select[name*="[area_level_"]').each(function(){
			console.log($(this).val())
			if($(this).val() != 0){
				gotSelVal = 1;
			}
		})
		if(gotSelVal == 0){
			errors += "Area\n";
			bool = false
		}*/
		if(bool){
			$.mask({text: i18n.General.textSaving});
		}else{
			alert(i18n.General.textRequiredField+(errors.length >1?"s":"")+": \n"+errors);
		}
		return bool;
	},
	addAreaSwitching : function(){
		var areatype = ['area_education','area']
		for(p in areatype){
			$('select[name*="['+areatype[p]+'_level_"]').each(function(l,obj){
				$(obj).attr('areatype',areatype[p]);
				$(obj).change(function(o){
					var TotalAreaLevel = $('select[name*="['+$(this).attr('areatype')+'_level_"]').length;
					var currentSelect = $(this).attr('name').replace('data[InstitutionSite]['+$(this).attr('areatype')+'_level_','');
					currentSelect = currentSelect.replace(']','');
					currentSelect = parseInt(currentSelect);
					for(i=currentSelect+1;i<TotalAreaLevel;i++){
						$('select[name=data\\[InstitutionSite\\]\\['+$(this).attr('areatype')+'_level_'+i+'\\]]').find('option').remove();
					};
					objInstitution.fetchChildren(this);
				});
			});
		}
    },
	fetchChildren :function (currentobj){
        var selected = $(currentobj).val();
        var edutype = $(currentobj).closest('fieldset').find('legend').attr('id');
        var maskId;
        var url =  getRootURL() +'/Areas/viewAreaChildren/'+selected+'/'+edutype;
        var level = '&nbsp;&nbsp;';
        $.when(
                $.ajax({
                    type: "GET",
                    url: getRootURL() +'/Areas/getAreaLevel/'+selected+'/'+edutype,
                    success: function (data) {
                        level = data;
                        var myselect = $(currentobj).parent().parent().find('select');
                        var myLabel = myselect.parent().parent().find('.label');
                        myLabel.show();
                        if(level=='&nbsp;&nbsp;'){
                            myLabel.html('(Area Level)');
                        }else{
                            myLabel.html(level);
                        }
                    }
                })
            ).then(function() {
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: url,
                    beforeSend: function (jqXHR) {
                        // maskId = $.mask({parent: '.content_wrapper'});
                        maskId = $.mask({parent: '#area_section_group', text: i18n.General.textLoadAreas});
                    },
                    success: function (data, textStatus) {
                        var callback = function(data) {
                            tpl = '';
                            var nextselect = $(currentobj).parent().parent().next().find('select');
                            var nextLabel = nextselect.parent().parent().find('.label');
                            //data[1] += nextLabel.text().toUpperCase(); // Add "ALL <text>" option in the select element
                            var counter = 0;
                            $.each(data,function(i,o){
                                tpl += '<option value="'+i+'">'+o+'</option>';
                                counter +=1;
                            });
                            if(level=='&nbsp;&nbsp;' || counter <2){
                                nextrow.hide();
                            }else{
                                nextrow.show();
                                nextLabel.removeClass('disabled');
                                nextLabel.html('(Area Level)');
                                nextselect.find('option').remove();
                                nextselect.removeAttr('disabled');
                                nextselect.append(tpl);
                            }
                            var myselect = nextselect.parent().parent().next().find('select');
                            do{
                                myselect.parent().parent().hide();
                                myselect = myselect.parent().parent().next().find('select');
                            }while(myselect.length>0)
                        };
                        $.unmask({ id: maskId,callback: callback(data)});
                    }
                });
            });
    },
    cancelAddSite: function(id){
		window.location = getRootURL()+"Institutions/listSites/"+id;
	}
}
