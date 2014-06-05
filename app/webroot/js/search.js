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
			
			var maskId;
			$.ajax({ 
				type: "post",
				url: getRootURL() + $(this).closest('thead').attr('url'),
				data: {order:order,sortdir : sort},
				beforeSend: function (jqXHR) {
					maskId = $.mask({parent: '.content_wrapper', text: i18n.Search.textSorting});
				},
				success: function(data){
					var callback = function() {
						$('#mainlist').html(data).promise().done(function(){
							objSearch.attachSortOrder();
							jsTable.attachHoverOnClickEvent();
						});
					};
					$.unmask({id: maskId, callback: callback});
				}
			});
		});
	},
	
	attachAutoComplete: function() {
		// advanced search
		$("#area").autocomplete({
			source: "advanced",
			minLength: 2,
			select: function(event, ui) {
				$('#area').val(ui.item.label);
				$('#area_id').val(ui.item.value);
				return false;
			}
		});
		$( "#area" ).change(function() {
		  	if($('#area').val()==""){
		  		$('#area_id').val("");
		  	}
		});
	},
	
	callback: function(data) {
		$("#mainlist").html(data);
		$('.total span').text($('.table').attr('total'));
		objSearch.attachEvents();
	}
};

var objCustomFieldSearch = {
    
    initTabs : function(){
        //Default Action fir Tabs
        //$(".tab_content").hide(); //Hide all content
        //$("ul.tabs li:first").addClass("active").show(); //Activate first tab
        $(".tab_content:first").show(); //Show first tab content

        //On Click Event
        $("ul.tabs li").click(function() {
            $("ul.tabs li").removeClass("active"); //Remove any "active" class
            $(this).addClass("active"); //Add "active" class to selected tab
            $(".tab_content").hide(); //Hide all tab content
            var activeTab = $(this).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
            $(activeTab).fadeIn(); //Fade in the active content
            return false;
        });
    },
    getDataFields : function (site,customfield){
    if(!customfield){
        customfield = 'InstitutionSite';
    }
    if (site >= 0){
        $.ajax({
          type      :  'GET',
          url       :  'getCustomFieldsSearch/'+site+'/'+customfield,
          success   :  function(data) {
            // process data here
            $('#CustomFieldDiv').html(data);
          }
        });
    }
}
    
    
}
