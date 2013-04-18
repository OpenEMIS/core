$(document).ready(function() {
	setup.init();
});

var setup = {
	init: function() {
		$('#edit-link').click(setup.navigate);
		$('#setup-variables .icon_plus').each(function() {
			$(this).click(function() { setup.add(this); });
		});
		$('#school_year .icon_plus').click(setup.addYear);
		$('#banks .icon_plus').click(function() { setup.addBank(this); });
		
		$('.input_radio').parent().find('[type="hidden"]').val(0);
		$('.btn_cancel').each(function() { $(this).click(setup.navigate); });
	},
	
	changeCategory: function() {
		var href = $('.content_wrapper > form').attr('action');
		window.location.href = href + '/' + $('#category').val();
	},
	
	navigate: function() {
		window.location.href = $('#edit-link').attr('href') + '/' + $('#category').val();
		return false;
	},
	
	toggleRadio: function(obj) {
		var parent = $(obj).closest('.table_body');
		parent.find('input[type="radio"]').removeAttr('checked');
		$(obj).attr('checked', 'checked');
	},
	
	add: function(obj) {
		var section = $(obj).closest('fieldset');
		if(section.length==0) { return; }
		var list = section.find('.quicksand');
		var lastRow = list.find('li:last');
		var order = 0;
		var model = section.find('#model').val();
		
		if(lastRow.length > 0) {
			order = lastRow.find('#order').val();
		}
		
		var conditions = {};
		section.find('[conditionName]').each(function() {
			conditions[$(this).attr('conditionName')] = $(this).val();
		});
		
		var maskId;
		var url = getRootURL() + 'Setup/setupVariablesAddRow';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {model: model, order: order, index: $('.quicksand li').length, conditions: conditions},
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, parent: section, text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					list.append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	addYear: function() {
		var parent = $(this).closest('.content_wrapper');
		var list = parent.find('.table_body');
		var index = list.find('.table_row').length;
		
		var maskId;
		var url = getRootURL() + 'Setup/setupVariablesAddYear';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {index: index},
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, parent: parent, text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					list.append(data);
					jsTable.fixTable();
					jsDate.initDatepicker('.new_row:last .datepicker');
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	addBank: function(obj) {
		var section = $(obj).closest('.section_group');
		if(section.length==0) { return; }
		var list = section.find('.quicksand');
		var lastRow = list.find('li:last');
		var order = 0;
		var model = section.find('#model').val();
		
		if(lastRow.length > 0) {
			order = lastRow.find('#order').val();
		}
		
		var conditions = {};
		section.find('[conditionName]').each(function() {
			conditions[$(this).attr('conditionName')] = $(this).val();
		});
		
		var maskId;
		var url = getRootURL() + 'Setup/setupVariablesAddBank';
		
		$.ajax({
			type: 'GET',
			dataType: 'text',
			url: url,
			data: {model: model, order: order, index: $('.quicksand li').length, conditions: conditions},
			beforeSend: function (jqXHR) {
				maskId = $.mask({id: maskId, parent: section, text: i18n.General.textAddingRow});
			},
			success: function (data, textStatus) {
				var callback = function() {
					list.append(data);
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	},
	
	updateYear: function() {
		$('.new_row').each(function() {
			var obj = $(this);
			var startYear = obj.find('.start_date').val().split('-').shift();
			var endYear = obj.find('.end_date').val().split('-').shift();
			obj.find('.start_year').val(startYear);
			obj.find('.end_year').val(endYear);
		});
		return true;
	}
};