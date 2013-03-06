$(document).ready(function() {
	custom.attachModelRedirect();
	custom.attachSiteTypeRedirect();
	custom.attachAddOptionEvent();
	custom.init();
});

var custom = {
	init: function() {
		custom.attachVisibleEvent();
		custom.attachMoveEvent();
		custom.attachIconTitle();
		custom.changeView();
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
		$('.icon_up').attr('title', i18n.General.iconMoveUp);
		$('.icon_down').attr('title', i18n.General.iconMoveDown);
		$('.icon_visible').attr('title', i18n.General.iconToggleField);
		$('.tooltip').remove();
		$('span[title]').tooltip({position: 'top center', effect: 'slide'});
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
	
	attachModelRedirect: function() {
		$('#customfieldchoices').change(function(){
			var sitetypeid = ($('#siteTypeid').val() == undefined) ? '' : $('#siteTypeid').val();
			var urlloc = ($('#edit-link').attr('href') == undefined) ? 'Edit' : '';
			if($(this).val() == 'CensusGrid')
				url = getRootURL()+'Setup/customTables';
			else
				url = getRootURL()+'Setup/customFields'+urlloc+'/'+$(this).val()+'/'+sitetypeid;
			
			
			
			window.location = url;
		});
	},
	
	attachSiteTypeRedirect: function() {
		$('#siteTypeid').change(function(){
			var model = ($('#customfieldchoices')) ? $('#customfieldchoices').val() :'';
			var urlloc = ($('#edit-link').attr('href') == undefined) ? 'Edit' : '';
			window.location = getRootURL()+'Setup/customFields'+urlloc+'/'+model+'/'+$(this).val();
		});
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
		var siteType = $('#siteTypeid').val()!=undefined ? $('#siteTypeid').val() : 0;
		
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
	changeView : function() {
        var selectedText = $('#customfieldchoices option:selected').text();
        var selectedValue = $('#customfieldchoices option:selected').val();

        // getting the link
        var link = $('a[id$="-link"]');
        var linkText = link.text();

        // if link is edit, set redirection to point to /Setup/customFieldsEdit/TeacherCustomField/
        if (linkText == "Edit") {
        	link.removeAttr("href");
        	link.attr('href', getRootURL() + 'Setup/customFieldsEdit/' + selectedValue);
        } else if (linkText == "View") {
        	// if view, then /Setup/customFields/TeacherCustomField/ 
        	link.removeAttr("href");
        	link.attr('href', getRootURL() + 'Setup/customFields/' + selectedValue);
        }

        // if site type id is available, append to the field
        var siteTypeId = $('#siteTypeid').val();
        console.info(siteTypeId);
        if (siteTypeId != "" && siteTypeId !== undefined && siteTypeId != null) {
        	var _href = link.attr("href");
        	link.attr('href', _href + '/' + siteTypeId);
        }
	}
};