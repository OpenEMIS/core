/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright � 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

jQuery.fn.reverse = [].reverse;

$(document).ajaxSend(function() {
	jsAjax.calls = jsAjax.calls + 1;
});

$(document).ajaxComplete(function() {
	jsAjax.calls = jsAjax.calls - 1;
	jsForm.linkVoid();
});

$(document).ready(function() {	
	jsForm.init();
	jsTable.init();
	jsList.init();
    /*if($('html').attr('dir')=="rtl"){
        jsForm.fixedBracket(); // This fix arabic translation brackets problem
    }*/
});

var dataStorage = {};

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }
String.prototype.isEmpty = function() { return this.trim().length == 0; }
String.prototype.toInt = function() { return parseInt(this, 10); }

var utility = {
	getKeyPressed: function(evt) {
		var keynum;
		if(window.event) { keynum = evt.keyCode; } // IE
		else if(evt.which) { keynum = evt.which; } // Netscape/Firefox/Opera
		return keynum;
	},
	
	integerCheck: function(evt) {
		var keynum = utility.getKeyPressed(evt);
		return ((keynum >= 48 && keynum <= 57) || keynum < 32 || keynum==undefined);
	},
	
	floatCheck: function(evt) {
		var keynum = utility.getKeyPressed(evt);
		return ((keynum >= 48 && keynum <= 57) || keynum < 32 || keynum==46 || keynum==undefined);
	},
        
        FTECheck: function(evt) {
		var keynum = utility.getKeyPressed(evt);
                
		return ((keynum >= 48 && keynum <= 57) || keynum < 32 || keynum==46 || keynum==190 || keynum==undefined);
	},
	
	br2nl: function(str, newline) {
		return str.replace(/(<br \/>)|(<br>)|(<br\/>)/g, newline ? '\n' : '');
	},
	
	nl2br: function(str) {
		return str.replace(/\n/g, '<br />\n');
	},
	
	basename: function(path, suffix) {
		var b = path.replace(/^.*[\/\\]/g, '');
		
		if (typeof(suffix) == 'string' && b.substr(b.length - suffix.length) == suffix) {
			b = b.substr(0, b.length - suffix.length);
		}
		return b;
	},

	addslashes: function(str) {
		return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
	},

	emailValidation: function(str) {
		var regex = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
		if (!regex.test(str)) {
			return false;
		}
		return true;
	},

	dateValidation: function(dateStr) {

		// check if in expected format (2010-01-25)
	    if (dateStr.search(/^\d{4}[\/|\-|\.|_]\d{1,2}[\/|\-|\.|_]\d{1,2}/g) != 0)
	        return false;
	  
	    // remove other separators invalid with the Date class              
	    dateStr = dateStr.replace(/[\-|\.|_]/g, "/");
	               
	    // convert it into a date instance   
	    var dt = new Date(Date.parse(dateStr));
	  
	    // check the components of the date   
	    // since Date instance automatically rolls over each component   
	    var arrDateParts = dateStr.split("/");   
	    return (
	        dt.getMonth() == arrDateParts[1]-1 &&   
	        dt.getDate() == arrDateParts[2] && 
	        dt.getFullYear() == arrDateParts[0]
	    );  
	},
	
	charLimit: function (field, maxNum) {
		// limit the num of chars, default 1000
		if (maxNum === "" || maxNum == undefined || maxNum === 0) {
			maxNum = 1000;
		}
		if (field.value.length > maxNum) {
			field.value = field.value.substring(0, maxNum);
		}
	}
};

var jsAjax = {
	calls: 0,
	hasPendingAjax: function() {
		return jsAjax.calls > 0;
	},
	result: function(opt) {
		var data = opt.data;
		var callback = opt.callback;
		
		if(data != null && data.type != undefined) {
			if(data.type == ajaxType.success) {
				if(callback != undefined) callback.call();
			} else if(data.type == ajaxType.alert) {
				var type = data.alertType == undefined ? alertType.ok : data.alertType;
				var alertOpt = {};
				for(var key in data.alertOpt) {
					alertOpt[key] = data.alertOpt[key];
				}
				alertOpt['type'] = type;
				$.alert(alertOpt);
				if(type == alertType.ok && callback != undefined) {
					callback.call();
				}
			} else {
				var dlgOpt = {
					id: 'error-dialog',
					content: data.msg,
					title: i18n.App.dlgOptErrorDialog
				};
				$.dialog(dlgOpt);
			}
		}
	}
};

var jsForm = {
	init: function() {
		$('input[type="number"]').keypress(function(evt) {
			return utility.integerCheck(evt);
		});
		this.linkVoid();
		this.initInputFocus('.input_wrapper input, .input_wrapper textarea');
		
		//$('.datepicker select').change(jsForm.datepickerUpdate);
		$('select.areapicker').change(jsForm.areapickerUpdate);
		jsForm.areapickerStart();
		
		// alert
		$('.alert_view[title]').click(function() {
			$(this).fadeOut(300, function() { $(this).remove(); });
		});
        //this.datepickerUpdateSelector();
	},
	
	change: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('url') + '/' + $(obj).val();
	},
	
	initDatepicker: function(p) {
		$(p).find('.datepicker select').change(jsForm.datepickerUpdate);
	},
	
	initInputFocus: function(element) {
		$(element).focusin(function() {
			$(this).closest('.input_wrapper').addClass('focus');
		}).focusout(function() {
			$(this).closest('.input_wrapper').removeClass('focus');
		});
	},
	
	linkVoid: function(id) {
		var element = id!=undefined ? id + ' a.void' : 'a.void';
		$(element).each(function() {
			$(this).attr('href', 'javascript: void(0)');
		});
	},
	
	toggleSelect: function(obj) {
		var table = $(obj).closest('.table');
		table.find('.table_body input[type="checkbox"]').each(function() {
			var row = $(this).closest('.table_row');
			if(obj.checked) {
				if($(this).attr('disabled') == undefined){
					$(this).attr('checked','checked');
					if(row.hasClass('inactive')) {
						row.removeClass('inactive');
					}
				}
			} else {
				$(this).removeAttr('checked');
				if(!row.hasClass('inactive')) {
					row.addClass('inactive');
				}
			}
		});
	},
	
	areapickerStart: function(){
		$('.areapicker_areaid').each(function(){
			var areaLevel = $(this).parent().parent().parent().find('select.areapicker');
			areaLevel.each(function(index) {
				if ($(this).val() ==0) {
					var fetchIndex = (index == 0 ? 0 : (index-1) );
					jsForm.getAreaChildren(areaLevel[fetchIndex]);
					return false;
				}
			});
		});
	},
	
	areapickerUpdate: function() {
		var areaItemSelected=$(this);

		var hiddenValue= $(this).parents().find('.areapicker_areaid').first();
		var myAreaArr = ["area_level","area_education_level"];
        for (var i = 0; i < myAreaArr.length; i++) {
            var areaItems = $(this).parent().parent().parent().find('select[name*="['+myAreaArr[i]+'_"]');
            areaItems.reverse().each(function(index) {
                if (areaItemSelected.is($(this))){
                    var tmpVal=$(this).val();
                    if (tmpVal != 0 && !!tmpVal) {
                        hiddenValue.val(tmpVal);
                    }
                    jsForm.getAreaChildren(this);
                    return false;
                } else {
                    //for some reason , some options drop down have "--selected" and some not . flush all options and re-add
                    $(this).find('option').remove();
                    $(this).append($('<option>', {value: 0,text: '--Select--'}));
                }
            });
        }
	},
	
	getAreaChildren :function (currentobj){
        var selected = $(currentobj).val();
        var edutype = $(currentobj).closest('fieldset').find('legend').attr('id');
        var maskId;
        var url =  getRootURL() +'/Areas/viewAreaChildren/'+selected+'/'+edutype;
        var level = '&nbsp;&nbsp;';
        var parentLegend = $('legend#parent_level');
        var childrenLegend = $('legend#children_level');
        childrenLegend.html(i18n.Areas.AreaLevelText);
        $.when(
            $.ajax({
                type: "GET",
                url: getRootURL() +'/Areas/getAreaLevel/'+selected+'/'+edutype,
                success: function (data) {
                    level = data;
                    var myselect = $(currentobj).parent().parent().find('select');
                    var myLabel = myselect.parent().parent().find('.label');
                    myLabel.show();
                    if(level=='&nbsp;&nbsp;'){
                        myLabel.html(i18n.Areas.AreaLevelText);
                    }else{
                        myLabel.html(level);
                        parentLegend.html(level);
                    }
                }
            })
        ).then(function() {
            $.ajax({
                type: 'GET',
                dataType: 'json',
                url: url,
                beforeSend: function (jqXHR) {
                    // maskId = $.mask({parent: '.content_wrapper'});
                    maskId = $.mask({parent: '#area_section_group', text: i18n.General.textLoadAreas});
                },
                success: function (data, textStatus) {
                    var callback = function(data) {
                        tpl = '';
                        var nextselect = $(currentobj).parent().parent().next().find('select');
                        var nextLabel = nextselect.parent().parent().find('.label');
                        var nextrow = $(currentobj).parent().parent().next('.row');
                        //data[1] += nextLabel.text().toUpperCase(); // Add "ALL <text>" option in the select element
                        var counter = 0;
                        $.each(data,function(i,o){
                            tpl += '<option value="'+i+'">'+o+'</option>';
                            counter +=1;
                        });
                        if(level=='&nbsp;&nbsp;' || counter <2){
                            nextrow.hide();
                        }else{
                            nextrow.show();
                            nextLabel.removeClass('disabled');
                            nextLabel.html(i18n.Areas.AreaLevelText);
                            nextselect.find('option').remove();
                            nextselect.removeAttr('disabled');
                            nextselect.append(tpl);
                        }
                        var myselect = nextselect.parent().parent().next().find('select');
                        do{
                            myselect.parent().parent().hide();
                            myselect = myselect.parent().parent().next().find('select');
                        }while(myselect.length>0)
                    };
                    $.unmask({ id: maskId,callback: callback(data)});
                }
            });
        });
    },

	updateDatepickerValue: function(parent, date) {
		var day = date.getDate();
		var mth = date.getMonth()+1;
		var yr = date.getFullYear();
		if(mth < 10) {
			mth = '0' + mth;
		}
		$(parent).find('.datepicker_day').val(day);
		$(parent).find('.datepicker_month').val(mth);
		$(parent).find('.datepicker_year').val(yr);
		$(parent).find('.datepicker_date').val(yr + '-' + mth + '-' + day);
	},
	
	datepickerUpdate: function() {
		var parent = $(this).parent();
		var dayValue = 1;
		var dayObj = parent.find('.datepicker_day');
		var monthValue = parent.find('.datepicker_month').val();
		var yearValue = parent.find('.datepicker_year').val();
		var dateObj = parent.parent().find('.datepicker_date');
		if(monthValue !=0 && yearValue !=0) {
			if(dayObj.length>0 && dayObj.val() > 0) {
				dayValue = dayObj.val();
			}
			var dateObj1 = new Date(yearValue, monthValue, 0);
			var dateObj2 = new Date(yearValue, monthValue-1, dayValue);
			if(dateObj1.getMonth() != dateObj2.getMonth()) {
				dayValue = dateObj1.getDate();
				dayObj.val(dayValue);
			}
			dateObj.val(yearValue + '-' + monthValue + '-' + dayValue);
		} else {
			dateObj.val('0000-00-00');
		}
	},
	
	confirmDelete: function(obj) {
		var href = $(obj).attr('href');
		if($(obj).prop('tagName') !== 'A') {
			href = getRootURL() + href;
		}
		var btn = {
			value: i18n.General.textDelete,
			callback: function() { window.location.href = href; }
		};
		
		var dlgOpt = {	
			id: 'delete-dialog',
			title: i18n.General.textDeleteConfirmation,
			content: i18n.App.confirmDeleteContent,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
		return false;
	},

	confirmClearAll: function(obj) {
		var href = $(obj).attr('href');
		if($(obj).prop('tagName') !== 'A') {
			href = getRootURL() + href;
		}
		var btn = {
			value: i18n.General.textDelete,
			callback: function() { window.location.href = href; }
		};
		
		var dlgOpt = {	
			id: 'delete-dialog',
			title: i18n.General.textWarningConfirmation,
			content: i18n.App.confirmClearAllContent,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
		return false;
	},

	confirmActivate: function(obj) {
		var href = $(obj).attr('href');
		if($(obj).prop('tagName') !== 'A') {
			href = getRootURL() + href;
		}
		var btn = {
			value: i18n.General.textConfirm,
			callback: function() { window.location.href = href; }
		};
		
		var dlgOpt = {	
			id: 'delete-dialog',
			title: i18n.General.textConfirmation,
			content: i18n.Training.confirmActivateMessage,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
		return false;
	},

	confirmInactivate: function(obj) {
		var href = $(obj).attr('href');
		if($(obj).prop('tagName') !== 'A') {
			href = getRootURL() + href;
		}
		var btn = {
			value: i18n.General.textConfirm,
			callback: function() { window.location.href = href; }
		};
		
		var dlgOpt = {	
			id: 'delete-dialog',
			title: i18n.General.textConfirmation,
			content: i18n.Training.confirmInactivateMessage,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
		return false;
	},


    datepickerUpdateSelector: function() {

        $('.datepicker').each(function(i, o){
            var dateOpenDatepicker = $(o);
            var hiddenDate = dateOpenDatepicker.siblings('input[type="text"].datepicker_date');
            if(hiddenDate.val() !== ''){
                var dateOpenValue = hiddenDate.val();

                if(typeof dateOpenValue !== "undefined"){
                    var splitDate = dateOpenValue.split('-');

                    if(dateOpenDatepicker.find('.datepicker_day').length > 0){
                        dateOpenDatepicker.find('.datepicker_day').val(function(){
                            return ('0' + splitDate.pop()).slice(-2);
                        });

                    }else{
                        splitDate.pop();
                    }

                    dateOpenDatepicker.find('.datepicker_month').val(splitDate.pop());
                    dateOpenDatepicker.find('.datepicker_year').val(splitDate.pop());
                }
            }
        });
    },
	
	isSubmitDisabled: function(form) {
		return !$(form).find('input[type="submit"]').hasClass('btn_disabled');
	},

    fixedBracket: function(){
        var replaced = $("body").html().replace(/\)/g,')&#x200E;');
        $("body").html(replaced);
    }
};

var jsTable = {
	init: function() {
		this.fixTable();
		this.attachHoverOnClickEvent();
	},
	
	attachHoverOnClickEvent: function() {
		$('.table.allow_hover').each(function() {
			var table = $(this);
			if(table.attr('action')!=undefined) {
				table.find('.table_row').each(function() {
					var rowId = $(this).attr('row-id')!=undefined ? $(this).attr('row-id') : '';
					$(this).click(function() {
						window.location.href = getRootURL() + table.attr('action') + rowId;
					});
				});
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
		
		if($(data).hasClass('alert')) {
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

var jsList = {
	isSorting: false,
	
	init: function(list) {
		var id = list==undefined ? '.table_view' : list;
		$(id).each(function() {
			$(this).find('li.li_even').removeClass('li_even');
			$(this).find('li:odd').addClass('li_even');
		});
		$('.table_view select').change(function() { jsList.attachSelectedEvent(this); });
	},
	
	attachSelectedEvent: function(obj) {
		var value = $(obj).val();
		$(obj).find('option[value="' + value + '"]').attr('selected', 'selected');
	},
	
	activate: function(obj, opts) {
		var selector = opts==undefined ? '[data-id]' : opts;
		var li = $(obj).closest(selector);
		if(li.hasClass('inactive')) {
			li.removeClass('inactive');
		} else {
			li.addClass('inactive');
		}
		
		if(li.find('#order').length==1) {
			// reorder the list to put inactive rows to the bottom of the list
			var list = li.parent();
			var order = 1;
			var inactives = [];
			list.find(selector).each(function() {
				var orderInput = $(this).find('#order');
				if(orderInput.length==1) {
					if($(this).hasClass('inactive')) {
						inactives.push(orderInput);
					} else {
						orderInput.val(order++);
					}
				}
			});
			for(var i in inactives) {
				inactives[i].val(order++);
			}
			jsList.doSort(obj, {swap: false});
		}
	},
	
	doSort: function(obj, opt) {
		if(opt == undefined) opt = {};
		var rowTag 	 = opt['row'] == undefined ? 'li' : opt['row'];
		var listTag  = opt['list'] == undefined ? '.quicksand' : opt['list'];
		var orderVal = opt['order_by'] == undefined ? '> #order' : opt['order_by'];
		var speed	 = opt['speed'] == undefined ? 300 : opt['speed'];
		var func	 = opt['callback'] == undefined ? null : opt['callback'];
		var render	 = opt['render'] == undefined ? true : opt['render'];
		var swap	 = opt['swap'] == undefined ? true : false;
		
		var row = $(obj).closest(rowTag);
		var tempRow = $(obj).hasClass('icon_up') ? row.prev() : row.next();
		var tempRank;
		var order = row.find(orderVal).val();
	
		if(swap && tempRow.length!=0) {
			tempOrder = tempRow.find(orderVal).val();
			tempRow.find(orderVal).val(order);
			row.find(orderVal).val(tempOrder);
		}
		
		var app = row.closest(listTag);
		var data = app.clone();
		var filteredData = data.find(rowTag);
		var sortedData = filteredData.sorted({
			by: function(v) {
				return $(v).find(orderVal).val().toInt();
			}
		});
		
		if(!jsList.isSorting) {
			jsList.isSorting = true;
			var callback = function() {
				jsList.isSorting = false;
				$('.quicksand').css('height', 'auto');
				if(render) { jsList.init(); }
				if(func != null) {
					func.apply();
				}
			};
			app.quicksand(sortedData, {duration: speed, adjustHeight: false}, callback);
		}
	},
	
	doRemove: function(obj) {
		var row = $(obj).closest('.new_row');
		var list = row.closest('.table_view');
		row.remove();
		jsList.init(list);
		list.find('li').each(function(i) {
			$(this).find('#order').val(i+1);
		});
	}
};

var utils = {
	
	printerF : function (){
		var w = window.open('', 'P', 'width=720,height=520,resizeable,scrollbars');
		w.document.write('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"  "http://www.w3.org/TR/html4/strict.dtd">');
		$.each($('link'),function(i,o){
			w.document.write(o.outerHTML);
		});
		w.document.write('<style type="text/css"> body{ min-width:500px !important; } </style>');
		w.document.write('<div style="margin:20px 50px">');
		var p = $('.body_content_right').clone();
		p.find("a").removeAttr("href");
		w.document.write(p.html());
		w.document.write('</div>');
		w.document.close(); // needed for chrome and safari
		w.focus();
		w.print();
	}
}


