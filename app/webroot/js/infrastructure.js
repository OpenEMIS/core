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
