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
    cancelAddSite: function(id){
		window.location = getRootURL()+"Institutions/listSites/"+id;
	}
}
