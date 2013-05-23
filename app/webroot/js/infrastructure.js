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

var Infrastructure = {
    init : function(){
        this.attachOnChange();
    },
    getCensusByMaterial : function (id,model){ console.log(model);
        var maskId;
        var parent = $('#'+model).closest('.section_group');
		var gender = ($('#'+model+'Gender').val() == undefined)?'':'/'+$('#'+model+'Gender').val();
		
        $.ajax({
            type: "post",
            url: getRootURL()+"Census/infrastructureByMaterial/"+id+"/"+$('#SchoolYearId').val()+'/'+$('#is_edit').val()+'/'+model+gender,
            beforeSend: function (jqXHR) {
                    maskId = $.mask({parent: parent, text: i18n.General.textRetrieving});
            },
            success: function(data){
                
				$.unmask({
					id: maskId, 
					callback: function() {
						console.log("here");
						$('#'+model+'_section').html(data);
						var total = 0;
						parent.find('.table_row').each(function() {
							var val = $(this).find('.cell_total').html();
							if(val.length>0) {
								total += val.toInt();
							}
						});
						parent.find('.table_foot .cell_value').html(total);
					}
				});
            }
        });
    },
    attachOnChange : function (){
       var infra = ['Sanitation','Buildings'];
       $.each(infra,function(o,i){
		   $('#'+i+'category').change(function(a,v){
                Infrastructure.getCensusByMaterial($(this).val(),i);
            });
		  
	   });
	   
	   $('#SanitationGender').change(function(a,v){
			Infrastructure.getCensusByMaterial($('#Sanitationcategory').val(),'Sanitation');
		});
	   
    },
    computeTotal: function(obj) {
		var row = $(obj).closest('.table_row');
		var subtotal = 0;
		row.find('input[type="text"]').each(function() {
			if($(this).val().length>0) {
				subtotal += $(this).val().toInt();
			}
		});
		
		if(subtotal == 0) { subtotal = ''; }
		row.find('.cell_total').html(subtotal);
		
		var table = row.closest('.table');
		var total = 0;
		table.find('.table_row').each(function() {
			var val = $(this).find('.cell_total').html();
			if(val.length>0) {
				total += val.toInt();
			}
		});
		table.find('.table_foot .cell_value').html(total);
	}
} 
$(function(){
  Infrastructure.init();  
})
