var jsTable = {
	init: function() {
		this.fixTable();
		this.initICheck();
		this.initTableCheckable();
		//this.attachHoverOnClickEvent();
	},
	
	initICheck: function() {
		if ($.fn.iCheck) {
			$('.icheck-input').iCheck({
				checkboxClass: 'icheckbox_minimal-blue',
				radioClass: 'iradio_minimal-blue',
				inheritClass: true
			}).on ('ifChanged', function (e) {
				$(e.currentTarget).trigger ('change');
			});
		}
	},
	
	initTableCheckable: function() {
		if ($.fn.tableCheckable) {
			$('.table-checkable')
		        .tableCheckable ()
			        .on ('masterChecked', function (event, master, slaves) { 
			            if ($.fn.iCheck) { $(slaves).iCheck ('update'); }
			        })
			        .on ('slaveChecked', function (event, master, slave) {
			            if ($.fn.iCheck) { $(master).iCheck ('update'); }
			        });
		}
	},
	
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
	
	fixHeight: function(row, col) {
		var height, update;
		$(row).each(function() {
			height = $(this).find(col+':first').height();
			update = false;
			$(this).find(col).each(function() {
				if($(this).height() != height) {
					update = true;
					if($(this).height() > height) {
						height = $(this).height();
					}
				}
			});
			
			if(update) {
				$(this).find(col).height(height);
			}
		});
	},
	
	toggleTableScrollable: function(parent) {
		var hide = 'hidden';
		var active = 'scroll_active';
		selector = parent!=undefined ? parent : '.table_scrollable';
		$(selector).each(function() {
			var rows = $(this).find('.table_body .table_row:visible').length;
			var list = $(this).find('.list_wrapper');
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
			if(list.hasClass(hide)) {
				list.removeClass(hide);
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
		$(obj).closest('.table_row').remove();
		jsTable.fixTable();
	},
	
	computeSubtotal: function(obj) {
		var table = $(obj).closest('.table_body');
		var row = $(obj).closest('.table_row');
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
		table.siblings('.table_foot').find('.' + type).html(total);
	},
	
	computeTotal: function(obj) {
		var table = $(obj).closest('.table_body');
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
		$(table).siblings('.table_foot').find('.' + type).html(total);
	},
	
	computeAllTotal: function(p) {
		var total = {};
		var type;
		$(p + ' input[computeType]').each(function() {			
			type = $(this).attr('computeType');
			total[type] = (total[type] != undefined ? total[type] : 0) + ($(this).val().length>0 ? $(this).val().toInt() : 0);
		});
		if($(p + ' .table_body').length>0) {
			for(var i in total) {
				$(p + ' .table_foot .' + i).html(total[i]);
			}
		} else {
			$(p + ' .table_foot .cell_value').html(0);
		}
	}
};