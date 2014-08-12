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
	custom.attachAddOptionEvent();
	custom.init();
});

var custom = {
	init: function() {
		custom.attachVisibleEvent();
		custom.attachMoveEvent();
		custom.attachIconTitle();
	},
	
	changeSiteType: function(obj) {
		window.location.href = getRootURL() + $('#siteTypeId').attr('url') + $('#siteTypeId').val();
	},
	
	attachVisibleEvent: function(obj) {
		var actionList, optionList;
		var action = '.action .icon_visible';
		var option = '.options .icon_visible';
		if(obj!=undefined) {
			actionList = obj.find(action);
			optionList = obj.find(option);
		} else {
			actionList = $(action);
			optionList = $(option);
		}
		
		actionList.each(function() {
			var obj = $(this);
			obj.click(function() {
				var li = obj.closest('.field_list > div');
				if(li.hasClass('inactive')) {
					li.removeClass('inactive');
					li.find('> #visible').val(1);
				} else {
					li.addClass('inactive');
					li.find('> #visible').val(0);
				}
			});
		});
		
		optionList.each(function() {
			var obj = $(this);
			obj.click(function() {
				var li = obj.closest('.options > li');
				if(li.hasClass('inactive')) {
					li.removeClass('inactive');
					li.find('> #visible').val(1);
				} else {
					li.addClass('inactive');
					li.find('> #visible').val(0);
				}
			});
		});
	},
	
	attachMoveEvent: function(obj) {
		var actionList, optionList;
		var action = '.action .icon_up, .action .icon_down';
		var option = '.options .icon_up, .options .icon_down';
		if(obj!=undefined) {
			actionList = obj.find(action);
			optionList = obj.find(option);
		} else {
			actionList = $(action);
			optionList = $(option);
		}
		actionList.each(function() {
			$(this).click(function() { custom.reorderFields(this); });
		});
		optionList.each(function() {
			$(this).click(function() { custom.reorderOptions(this); });
		});
	},
	
	attachIconTitle: function() {
		
		try{
		$('.icon_up').attr('title', i18n.General.iconMoveUp);
		$('.icon_down').attr('title', i18n.General.iconMoveDown);
		$('.icon_visible').attr('title', i18n.General.iconToggleField);
		$('.tooltip').remove();
		$('span[title]').tooltip({position: 'top center', effect: 'slide'});
		}catch(e){}
	},
	
	attachAddOptionEvent: function() {
		$('.icon_plus').each(function() {
			$(this).click(function() { custom.addOption(this); });
		});
	},
	
	attachFieldAction: function(obj) {
		$('.field_list > div').each(function(i) {
			var index = i+1;
			$(this).attr('data-id', index);
			$(this).find('> #order').val(index);
		});
		custom.attachVisibleEvent(obj);
		custom.attachMoveEvent(obj);
		custom.attachIconTitle();
		obj.find('.icon_plus').click(function() { custom.addOption(this); });
		obj.find('.field_label input, legend input').select();
	},
	
	attachAction: function(obj) {
		obj.find('.icon_visible').click(function() {
			var obj = $(this);
			obj.click(function() {
				var li = obj.closest('.options > li');
				
				if(li.hasClass('inactive')) {
					li.removeClass('inactive');
					li.find('> #visible').val(1);
				} else {
					li.addClass('inactive');
					li.find('> #visible').val(0);
				}
			});
		});
		obj.find('.icon_up').click(function() { custom.reorderOptions(this); });
		obj.find('.icon_down').click(function() { custom.reorderOptions(this); });
	},
	
	reorderFields: function(obj) {
		jsList.doSort(obj, {
			row: 'div[data-id]',
			callback: function() { custom.init(); custom.attachAddOptionEvent(); }
		});
	},
	
	reorderOptions: function(obj) {
		jsList.doSort(obj, {callback: custom.init});
	},
	
	addField: function(type) {
		var list = $('.field_list');
		var model = $('#model').text();
		var field = $('#refField').text();
		var order = list.find('> div').length;
		var siteType = $('#siteTypeId').length>0 ? $('#siteTypeId').val() : 0;
		
		var url = getRootURL() + 'Setup/customFieldsAdd';
		var maskId;
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {type: type, model: model, order: order, field: field, siteType: siteType},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: '.row.add', text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					list.prepend(data);
					var obj = list.find('> div:first');
					custom.attachFieldAction(obj);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	addOption: function(obj) {
		var parent = $(obj).parent();
		var list = parent.parent().find('> .options');
		var fieldId = parent.find('> #refValue').text();
		var field = $('#refField').text();
		var model = $('#model').text();
		var order = $('.options > li').length;
		
		var url = getRootURL() + 'Setup/customFieldsAddOption';
		var maskId;
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {model: model, order: order, field: field, fieldId: fieldId},
			beforeSend: function (jqXHR) {
				maskId = $.mask({parent: list, text: i18n.General.textAddingOption});
			},
			success: function (data, textStatus) {
				var callback = function() {
					list.append(data);
					var obj = list.find('li:last');
					custom.attachAction(obj);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	view : {
		changeCategory : function(a,b){
			var site = (b) ? $('#institution_site_id').val() : '' ;
			location.href = getRootURL()+ $(a).attr('url')+'/'+$('#school_year_id').val()+'/'+ site;
		},
		redirect:function(a){
            location.href = $(a).attr('href') +'/'+$('#school_year_id').val();
			return false;
		}
	}
};
