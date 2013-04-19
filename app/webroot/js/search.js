$(document).ready(function() {
	objSearch.init();
});

var maskId;
var objSearch = {
	init: function() {
		$('.search .icon_clear').click(function() {
			$('#SearchField').val('');
			$('#searchbutton').click();
		});
		this.attachEvents();
	},
	
	attachEvents: function() {
		objSearch.attachRowClick();
		objSearch.attachSortOrder();
	},
	
	attachRowClick:function() {
		var url = getRootURL() + $('#controller').val() + '/' + $('#action').val() + '/';
		$('.search .allow_hover .table_row').click(function(e){ window.location = url+$(this).attr("id"); });
	},
	
	attachSortOrder:function() {
		$('[order]').click(function(){
			var order = $(this).attr('order');
			var sort =($(this).attr('class') == 'icon_sort_down')?'asc':'desc';
			$('[order]').attr('class','icon_sort_up')
			if(sort == 'desc') $(this).attr('class', 'icon_sort_down');
			else $(this).attr('class', 'icon_sort_up');
			$.ajax({ 
				type: "post",
				url: getRootURL() + $('#controller').val() + "/index",
				data: {order:order,sortdir : sort}, 
				success: function(data){
					$('#mainlist').html(data).promise().done(function(){
						objSearch.attachSortOrder();
						objSearch.attachRowClick();
					});
					
				}
			});
		});
	},
	
	callback: function(data) {
		$("#mainlist").html(data);
		objSearch.attachEvents();
	}
};