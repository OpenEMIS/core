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
		$('select[name*="[area_level_"]').each(function(i,obj){

			$(obj).change(function(o){
				var TotalAreaLevel = $('select[name*="[area_level_"]').length;
				var currentSelect = $(this).attr('name').replace('data[InstitutionSite][area_level_','');
				
				$('input[name*="[area_id"]').val($(this).val())
				currentSelect = currentSelect.replace(']','');
				currentSelect = parseInt(currentSelect);
				for(i=currentSelect+1;i<TotalAreaLevel;i++){
					//console.log($('select[name=data\\[InstitutionSite\\]\\[area_level_'+i+'\\]]'));
					$('select[name=data\\[InstitutionSite\\]\\[area_level_'+i+'\\]]').find('option').remove();
				}
				objInstitution.fetchChildren(this);

			});

		});
	},
    fetchChildren :function (currentobj){
		var selected = $(currentobj).val();
		var maskId;
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: getRootURL()+'/Institutions/viewAreaChildren/'+selected,
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
    cancelAddSite: function(id){
		window.location = getRootURL()+"Institutions/listSites/"+id;
	}
}
