/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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


var jsTable = {
	
	attachHoverOnClickEvent: function() {
		$('.table.allow_hover, .table.table-hover').each(function() {
			var table = $(this);
			if(table.attr('action')!=undefined) {
				table.find('.table_row').each(function() {
					var rowId = $(this).attr('row-id')!=undefined ? $(this).attr('row-id') : '';
					$(this).click(function() {
						window.location.href = getRootURL() + table.attr('action') + rowId;
					});
				});
			}
			var tbody = table.find('tbody');
			if(tbody.length!=0) {
				if(tbody.attr('action')!=undefined) {
					tbody.find('tr').each(function() {
						var rowId = $(this).attr('row-id')!=undefined ? $(this).attr('row-id') : '';
						$(this).click(function() {
							window.location.href = getRootURL() + tbody.attr('action') + rowId;
						});
					});
				}
			}
		});
	},
	
	fixTable: function(table) {
		var id = table==undefined ? '.table' : table;
		
		$(id).each(function() {
			var obj = $(this);
			if(obj.find('.table_head').length==1
			&& obj.find('.table_body').length==1
			&& obj.find('.table_foot').length==1
			&& obj.find('.table_body').html().isEmpty()) {
				obj.find('.table_body').remove();
			}
			if(!obj.hasClass('no_strips')) {
				obj.find('.table_body').each(function() {
					$(this).find('.table_row.even').removeClass('even');
					$(this).find('.table_row:visible:odd').addClass('even');
				});
				if(obj.hasClass('table-striped')) {
					$(this).find('tr.odd').removeClass('even');
					$(this).find('tr:visible:even').addClass('even');
				}
			}
		});
	},
	
	toggleTableScrollable: function(parent) {
		var hide = 'hidden';
		var active = 'scroll_active';
		selector = parent!=undefined ? parent : '.table_scrollable';
		$(selector).each(function() {
			var list = $(this).find('.list_wrapper');
			
			if(list.hasClass(hide)) {
				list.removeClass(hide);
			}
			
			var rows = $(this).find('.table_body .table_row:visible').length;
			var scrollable = list.closest('.table_scrollable');
			
			if(rows > list.attr('limit')) {
				if(!scrollable.hasClass(active)) {							
					scrollable.addClass(active);
				}
			} else {
				if(scrollable.hasClass(active)) {							
					scrollable.removeClass(active);
				}
			}
			
		});
	},
	
	tableScrollableAdd: function(parent, data) {
		var hide = 'hidden';
		var active = 'scroll_active';
		var scrollable = parent + ' .table_scrollable';
		var list = scrollable + ' .list_wrapper';
		var selector = list + ' .table_body';
		
		if($($.parseHTML(data)).hasClass('alert')) {
			var alertOpt = {
				id: 'scrollable_alert',
				parent: parent,
				position: 'center'
			}
			alertOpt['type'] = $(data).attr('type');
			alertOpt['text'] = $(data).html();
			$(scrollable).removeClass(active);
			if(!$(list).hasClass(hide)) {
				$(list).addClass(hide);
			}
			$.alert(alertOpt);
		} else {
			$(selector).append(data);
			jsTable.toggleTableScrollable(parent);
			jsTable.fixTable($(selector).parent());
		}
	},
	
	doRemove: function(obj) {
        if($(obj).closest('.table_row').length>0) {
            $(obj).closest('.table_row').remove();
        }
        if($(obj).closest('tr').length>0) {
            $(obj).closest('tr').remove();
        }
		jsTable.fixTable();
	},

	// To be complete
	doRemoveWithAppend: function(obj, selectObj) {
        if($(obj).closest('tr').length>0) {
            $(obj).closest('tr').remove();
            $(selectObj).append(
        		$('<option></option>').val(val).html(text)
        	);
        }
		jsTable.fixTable();
	},

	doRemoveColumn: function(obj) {
		var colnum = $(obj).closest("th").prevAll("th").length + 1;
		$(obj).closest("table").find("td:nth-child(" + colnum + "), th:nth-child(" + colnum + ")").remove();

		jsTable.fixTable();
	},
	
	computeSubtotal: function(obj) {
		var table = $(obj).closest('tbody');
		var row = $(obj).closest('tr');
		var type = $(obj).attr('computeType');
		var subtotal = 0;
		
		row.find('[computeType="' + type + '"]').each(function() {
			if($(this).val().isEmpty()) {
				if($(this).attr('allowNull')==undefined) {
					$(this).val(0);
					subtotal += $(this).val().toInt();
				}
			} else {
				subtotal += $(this).val().toInt();
			}
		});
		row.find('.cell_subtotal').html(subtotal);
		
		var total = 0;
		table.find('.cell_subtotal').each(function() {
			total += $(this).html().toInt();
		});
		table.siblings('tfoot').find('.' + type).html(total);
	},

	computeTotalForMoney: function(type) {
		var total = 0;
		$('#table_'+type).find('input[computeType="' + type + '"]').each(function() {
			if($(this).val().isEmpty()) {
				if($(this).attr('allowNull')==undefined) {
					$(this).val(0);
					total += parseFloat($(this).val()) || 0;
				}
			} else {
				total += parseFloat($(this).val()) || 0;
			}
		});
		$('#table_'+type).siblings('tfoot').find('.' + type).html(parseFloat(total).toFixed(2));
	},
	
	computeTotal: function(obj) {
		var table = $(obj).closest('tbody');
		var type = $(obj).attr('computeType');
		var total = 0;
		table.find('input[computeType="' + type + '"]').each(function() {
			if($(this).val().isEmpty()) {
				if($(this).attr('allowNull')==undefined) {
					$(this).val(0);
					total += $(this).val().toInt();
				}
			} else {
				total += $(this).val().toInt();
			}
		});
		$(table).siblings('tfoot').find('.' + type).html(total);
	},
	
	computeAllTotal: function(p) {
		var total = {};
		var type;
		$(p + ' input[computeType]').each(function() {			
			type = $(this).attr('computeType');
			total[type] = (total[type] != undefined ? total[type] : 0) + ($(this).val().length>0 ? $(this).val().toInt() : 0);
		});
		if($(p + ' tbody').length>0) {
			for(var i in total) {
				$(p + ' tfoot .' + i).html(total[i]);
			}
		} else {
			$(p + ' tfoot .cell_value').html(0);
		}
	},
			
	turncheckboxes: function(what){
		var  c = $('input.icheck-input');
		if(what == 'on'){
			c.iCheck('check');
		}else{
			c.iCheck('uncheck');
		}
	},
	
};