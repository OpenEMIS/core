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
	jsList.init();
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

	checkDecimal: function(obj, dec) {
		var regexStr = '[0-9]*';
		if(dec > 0) {
			regexStr += '\\.?[0-9]{0,' + dec + '}';
		} else if(dec < 0) {
			regexStr += '\\.?[0-9]*';
		}
		regexStr = '^' + regexStr;
		regexStr += '$';

		var regex = new RegExp(regexStr);
		if(regex.test(obj.value) == false) {
			obj.value = obj.value.substring(0, obj.value.length - 1);
		}
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
			if ($(this).attr("step") !== undefined) {
				return utility.floatCheck(evt);
			} else {
				return utility.integerCheck(evt);
			}
		});
		this.linkVoid();

		// to handle select box to change url for filtering
		$("select[url]").change(function() {
			jsForm.change($(this));
		});
	},

	// used by search.ctp
	submit: function() {
		var key = 'data-input-name';
		var form = $('form:first');

		$('[' + key + ']').each(function() {
			form.append($('<input>').attr({
				'type': 'hidden',
				'name': $(this).attr(key),
				'value': $(this).val()
			}));
		});

		form.submit();
	},

	compute: function(obj) {
		var total = 0;
		$("input[data-compute-variable=true]").each(
			function() {
				switch ($(this).attr('data-compute-operand')) {
					case 'plus': total += parseFloat($(this).val()) || 0; break;
					case 'minus': total -= parseFloat($(this).val()) || 0;break;
				}
				// console.log($(this).attr('data-compute-operand')+ ': '+(parseFloat($(this).val()) || 0));
			}
		);
		// console.log('total: '+ parseFloat(total).toFixed(2));
		$("[data-compute-target=true]").val(parseFloat(total).toFixed(2));
	},

	goto: function(obj) {
		window.location.href = getRootURL() + $(obj).attr('url');
	},

	change: function(obj) {
		var ret = [];
		var key = $(obj).attr('data-named-key');
		var group = $(obj).attr('data-named-group');

		if (key !== undefined) {
			ret.push(encodeURIComponent($(obj).attr('data-named-key')) + "=" + encodeURIComponent($(obj).val()));
			if (group != undefined) {
				var groupArray = group.split(',');
				for (var i in groupArray) {
					ret.push(encodeURIComponent(groupArray[i]) + "=" + encodeURIComponent($('[data-named-key=' + groupArray[i] + ']').val()));
				}
			}
		}

		var url = window.location.origin + $(obj).attr('url');
		var separator = url.indexOf('?') == -1 ? '?' : '&';
		if (key === undefined) {
			window.location.href = $.trim(url) + $(obj).val();
		} else {
			window.location.href = url+separator+ret.join("&");
		}
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

	isSubmitDisabled: function(form) {
		return !$(form).find('input[type="submit"]').hasClass('btn_disabled');
	},

    fixedBracket: function(){
        var replaced = $("body").html().replace(/\)/g,')&#x200E;');
        $("body").html(replaced);
    },

	doCopy: function(src, target){
		src.find(':input[type=hidden][data-field]').each(function(){
			var srcName = $(this).attr('data-field');
			var srcValue = $(this).val();
			target.find(':input[data-field]').each(function(){
				var targetName = $(this).attr('data-field');
				if(srcName == targetName) {
					$(this).val(srcValue);
				}
			});
		});
	}
};

var jsList = {
	isSorting: false,

	init: function(list) {
		$('.table_view select').change(function() { jsList.attachSelectedEvent(this); });

		$("[data-load-image=true]:first").each(function() {
			jsList.loadImage($(this));
		});
	},

	loadImage: function(obj) {
		var objRowId = obj.closest('[data-row-id]');
		var imageUrl = obj.attr('data-image-url');
		$.ajax({
			url: imageUrl+'/'+objRowId.attr('data-row-id'),
			type: "GET",
			data: {'base64':true},
			success: function(data) {
				var i = new Image();
				i.src = "data:image/jpg;base64," + data;
				i.onload = function(){
					var imageWidth = obj.attr('data-image-width');
					if (typeof imageWidth !== typeof undefined && imageWidth !== false) {
						i.width = imageWidth;
					}
					$(obj).find(".profile-image-thumbnail").empty().append(i);
					// remove loading icon
				};
				i.onerror = function(){
					// remove loading icon
				};
				$(obj).attr('data-load-image', false)
			},
			complete: function() {
				$("[data-load-image=true]:first").each(function() {
					jsList.loadImage($(this));
				});
			}
		});
	},

	attachSelectedEvent: function(obj) {
		var value = $(obj).val();
		$(obj).find('option[value="' + value + '"]').attr('selected', 'selected');
	},

	doRemove: function(obj) {
		var row = $(obj).closest('.new_row');
		var list = row.closest('.table_view');
		row.remove();
		jsList.init(list);
		list.find('li').each(function(i) {
			$(this).find('#order').val(i+1);
		});
	},

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

function pr(data) {
    data = data || '';
    // if (typeof data ==='array') {
    // 	for (var i in data) {
    // 		console.log(data[i]);
    // 	}
    // } else {
	    console.log(data);
    // }
}
