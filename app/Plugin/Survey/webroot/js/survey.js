/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


var Survey = {
	filterXML : function(){
		//var sy = $('#schoolYear').val();
		var sy = $("#schoolYear :selected").text();
		var st = $("#siteType :selected").text().replace(" ","-");
		var resp = (($("#pageType").val() == 'import')?'import':'');
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: getRootURL() + 'Survey/filter/'+resp,
			data: {schoolYear: sy,siteType:st},
			beforeSend: function (jqXHR) {
				//maskId = $.mask({parent: '.content_wrapper', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				/*var callback = function() {
					$('.file_upload .table_body').append(data);
				};
				$.unmask({id: maskId, callback: callback});
				*/
			   $("#results").html(data);
			   jsForm.attachHoverOnClickEvent();
				//console.log();
			}	
		});
	}
	
}