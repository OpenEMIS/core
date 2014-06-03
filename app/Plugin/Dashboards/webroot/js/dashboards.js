/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


var Dashboards = {
	areaChange : function(obj){
		$.ajax({
                type: 'GET',
                url: getRootURL()+$(obj).attr('url'),
				data: {levelId: $(obj).val()},
                beforeSend: function (jqXHR) {
                    // maskId = $.mask({parent: '.content_wrapper'});
                    maskId = $.mask({parent: '.dashboard_wrapper', text: i18n.General.textLoadAreas});
                },
                success: function (data, textStatus) {
					
					var callback = function(data) {
						$('#DashboardsAreaLevelId').html(data);
					};
					$.unmask({ id: maskId,callback: callback(data)});
					/*$.ajax({
						type: 'GET',
						url: getRootURL()+$('#DashboardsFdLevelId').attr('url'),
						data: {countryId: $(obj).val(), prependBlank:true},
						success: function (data, textStatus) {
							var callback = function(data) {
								$('#DashboardsFdLevelId').html(data);
							};
							$.unmask({ id: maskId,callback: callback(data)});
						}
					});*/
                }
            });
	},
	FDChange : function(obj){
		$.ajax({
                type: 'GET',
                url: getRootURL()+$(obj).attr('url'),
				data: {countryId: $(obj).val(), prependBlank:true},
                beforeSend: function (jqXHR) {
                    // maskId = $.mask({parent: '.content_wrapper'});
                    maskId = $.mask({parent: '.dashboard_wrapper', text: i18n.General.textLoadAreas});
                },
                success: function (data, textStatus) {
                    var callback = function(data) {
                        $('#DashboardsFdLevelId').html(data);
                    };
                    $.unmask({ id: maskId,callback: callback(data)});
                }
            });
	}
}