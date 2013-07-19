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
		objSearch.attachSortOrder();
		jsTable.attachHoverOnClickEvent();
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
				url: getRootURL() + $(this).closest('.table_head').attr('url'),
				data: {order:order,sortdir : sort}, 
				success: function(data){
					$('#mainlist').html(data).promise().done(function(){
						objSearch.attachSortOrder();
						jsTable.attachHoverOnClickEvent();
					});
					
				}
			});
		});
	},
	
	callback: function(data) {
		$("#mainlist").html(data);
		$('.total span').text($('.table').attr('total'));
		objSearch.attachEvents();
	}
};
