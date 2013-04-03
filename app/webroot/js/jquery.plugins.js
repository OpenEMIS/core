// Plugin Template

(function($){
    $.fn.extend({
        pluginname: function(options) {
			
			var defaults = {
				
			};
			
			var options =  $.extend(defaults, options);
			
            return this.each(function() {
				var o = options;
				var obj = $(this);
				
				//code to be inserted here
             
            });
        }
    });
	
})(jQuery);

// Function: Mask an element / page
jQuery.mask = function(opt) {
	var defaults = {
		id: '#mask',
		parent: 'window',
		text: i18n.General.textLoading,
		top: '30%',
		position: false
	};
	
	var o = $.extend(defaults, opt);
	var id = o.id;
	
	if(id==='#mask') {
		id += $('div.mask').length;
	}
	var txt = o.text;
	var p = o.parent;
	var wnd = p=='window';
	var position = wnd ? wnd : o.position;
	var top = o.top;
	var b = 'body';
	var w = wnd ? $(window).width() : $(p).width();
	var h = wnd ? $(b).height() : $(p).height();
	var loader = '<div id="' + id.replace('#', '') + '" class="mask">';
	loader += '<div id="loading-box">';
	loader += '<span class="loader"></span>'; // loader icon
	loader += '<span class="text">' + txt + '</span>';
	loader += '</div></div>';
	$(wnd ? b : p).prepend(loader);
	$(id).width(w).height(h);
	$(id + ' #loading-box').centerElement({wnd: wnd});
	if(position && top!=false) $(id + ' #loading-box').css('top', top);
	
	return id;
};

jQuery.unmask = function(opt) {
	var defaults = {
		id: '#mask',
		duration: 300,
		callback: function() {},
		callbackBefore: false,
		callbackAfter: true
	};
	
	var o = $.extend(defaults, opt);
	var id = o.id;
	var func = o.callback;
	
	if(o.callbackBefore && func != undefined) {
		func.apply();
	}
	$(id).fadeOut(o.duration, function() {
		$(this).remove();
		if(o.callbackAfter && func != undefined) {
			func.apply();
		}
	});
};

// Function: CenterElement

(function($){
    $.fn.extend({
        centerElement: function(options) {
			
			var defaults = {
				wnd: true,	// center to window (true) or parent (false)
				h: true,	// to center horizontally
				v: true,	// to center vertically
				top: false
			}
			
			var options =  $.extend(defaults, options);
			
            return this.each(function() {
				var o = options;
				var obj = $(this);
				var wnd = o.wnd;
				var h = o.h;
				var v = o.v;
				var top = o.top;

				if(wnd) {
					obj.css('position', 'fixed');
					if(h) {
						var windowWidth = $(window).width();
						var w = obj.outerWidth();
						obj.css('left', (windowWidth-w)/2);
					}
					if(v) {
						var windowHeight = $(window).height();
						var hh = obj.outerHeight();
						if(top == false) {
							obj.css('top', (windowHeight-hh)/2);
						} else {
							obj.css('top', top);
						}
					}
				}
				else {
					if(obj.parent().css('position') != 'absolute' && obj.parent().css('position') != 'fixed') {
						obj.parent().css('position', 'relative');
					}
					obj.css('position', 'absolute');
					if(h) {
						var pWidth = obj.parent().width();
						var w = obj.outerWidth();
						obj.css('left', (pWidth-w)/2);
					}
					if(v) {
						var pHeight = obj.parent().height();
						var h = obj.outerHeight();
						obj.css('top', (pHeight-h)/2);
					}
				}
            });
        }
    });
})(jQuery);

jQuery.getPageSize = function() {
	var viewportWidth, viewportHeight;
	
	if (window.innerHeight && window.scrollMaxY) {
		viewportWidth = document.body.scrollWidth;
		viewportHeight = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight) {
		// all but explorer mac
		viewportWidth = document.body.scrollWidth;
		viewportHeight = document.body.scrollHeight;
	} else {
		// explorer mac...would also work in explorer 6 strict, mozilla and safari
		viewportWidth = document.body.offsetWidth;
		viewportHeight = document.body.offsetHeight;
	}
	
	return {width: viewportWidth, height: viewportHeight};
};

// Function: dialog

jQuery.dialog = function(opt) {
	var defaults = {
		id: 'default-dialog',
		parent: 'body',
		title: i18n.General.textDialog,
		content: '',
		ajaxUrl: null, // specify the url to fetch the content, it will overwrite 'content' variable
		ajaxParam: {},
		top: '20%',
		width: 350,
		buttons: [],
		closeBtnCaption: i18n.General.textCancel,
		showCloseBtn: true,
		onBeforeOpen: function() {},
		onOpen: function() {},
		onBeforeClose: function() {},
		onClose: function() {},
		opacity: 0.7
	};
	
	var o = $.extend(defaults, opt);
	var id = o.id;
	var ajaxUrl = o.ajaxUrl;
	var ajaxParam = o.ajaxParam;
	var title = o.title;
	var content = o.content;
	var top = o.top;
	var w = o.width;
	var p = o.parent;
	var btns = o.buttons;
	var btnClass = 'dialog-btn';
	var dlgId = '#' + id;
	var dialog = dlgId + ' .dialog-box';
	var dialogMask = dlgId + '-mask';
	var opacity = o.opacity;
	
	var html = '<div id="' + id + '-mask" class="dialog-mask"></div>';
	html += '<div id="' + id + '" class="dialog-container"><div class="dialog-box"><div class="dialog-body">';
	html += '<h2>' + title + '</h2>';
	html += '<div class="dialog-content">' + content + '</div>';
	html += '<div class="dialog-controls">';
	
	if(btns.length==0 && o.closeBtnCaption == 'Cancel') {
		o.closeBtnCaption = 'Close';
	}
	var closeBtn = '<input id="dialog-btn-close" type="button" value="' + o.closeBtnCaption + '" />';
	
	// buttons parameters
	// Parameters: {id, value, callback}
	var btn = null;
	var btnFunc = {};
	var hasCloseBtn = false;
	
	// custom buttons
	if(btns.length != 0) {
		for(var i=0; i<btns.length; i++) {
			btn = btns[i];
			var type = btn.type == undefined ? 'button' : btn.type;
			if(btn != 'close') {
				var btnId = btn.id != undefined ? btn.id : 'dialog-btn-' + i;
				html += '<input id="' + btnId + '" type="' + type + '" class="' + btnClass + '" value="' + btn.value + '" />';
				btnFunc[btnId] = btn.callback;
			} else {
				if(o.showCloseBtn) {
					html += closeBtn;
					hasCloseBtn = true;
				}
			}
		}
	} else {
		if(o.showCloseBtn) {
			html += closeBtn;
			hasCloseBtn = true;
		}
	}
	
	if(o.showCloseBtn && !hasCloseBtn) {
		html += closeBtn;
	}
	
	html += '</div></div></div></div>';
	
	$(p).prepend(html);
	$('.dialog-mask').css('opacity', opacity);
	
	// to fix overlapping dialogs by calculating the z-index
	if($('.dialog-mask').length>1) {
		var zIndex = 0;
		$('.dialog-mask').each(function() {
			var index = $(this).css('z-index');
			if(index > zIndex) zIndex = parseInt(index, 10);
		});
		$(dlgId + ', ' + dialogMask).css('z-index', zIndex+1);
		zIndex = 0;
		$('.dialog-box').each(function() {
			var index = $(this).css('z-index');
			if(index > zIndex) zIndex = parseInt(index, 10);
		});
		$(dialog).css('z-index', zIndex+1);
	}
	
	$(dialog).width(w);
	// need fixing
	if($.browser.msie) $(dialog).css('background-color', '#969696');
	
	// to initialise onclick function for custom buttons
	for(var i in btnFunc) {
		$('#' + i).click(btnFunc[i]);
	}
	
	$(dlgId + ' #dialog-btn-close').click(function() {
		$.closeDialog({
			id: id,
			onBeforeClose: o.onBeforeClose,
			onClose: o.onClose
		});
	});
	
	if(p == 'body')
	{
		var pageSize = $.getPageSize();
		$(dlgId + ', ' + dialogMask).width(pageSize.width);
		$(dlgId + ', ' + dialogMask).height(pageSize.height);
		$(dialog).centerElement();
		// need to fix page jumping
		//var height = parseInt(pageSize.height * top.replace('%', '') / 100, 10);
		//console.log(height);
		$(dialog).css('top', top);
	}
	else
	{
		$(dlgId + ', ' + dialogMask).width($(p).width());
		$(dlgId + ', ' + dialogMask).height($(p).height());
		$(dialog).centerElement({wnd: false});
	}
	
	if(ajaxUrl != null) {
		var maskId = '#ajax_dialog_mask';
		$.ajax({
			type: "get",
			dataType: "text",
			url: ajaxUrl,
			data: ajaxParam,
			beforeSend: function (jqXHR) { $.mask({id: maskId}); },
			error: ajaxErrorHandler,
			complete: function (jqXHR, textStatus) {
				if(jqXHR.status != 200) {
					$(dlgId + ', ' + dialogMask).remove();
				}
			},
			success: function (data, textStatus) {
				var callback = function() {
					$(dialog + ' .dialog-content').html(data);
					o.onBeforeOpen.apply();
					$(dialogMask).fadeIn(300);
					$(dialog).fadeIn(300, function() {
						$(dialog + ' input[type="text"]:first').select();
						o.onOpen.apply();
					});
				};
				$.unmask({id: maskId, callback: callback});
			}
		});
	} else {
		o.onBeforeOpen.apply();
		$(dialogMask).fadeIn(300);
		$(dialog).fadeIn(300, function() {
			$(dialog + ' input[type="text"]:first').select();
			o.onOpen.apply();
		});
	}
};

jQuery.closeDialog = function(opt) {
	var defaults = {
		id: 'default-dialog',
		onBeforeClose: function() {},
		onClose: function() {}
	};
	
	var o = $.extend(defaults, opt);
	var id = o.id;
	
	o.onBeforeClose.apply();
	$('#' + id + '-mask').animate({'opacity':0}, 200, function() { 
		$('#' + id + '-mask').remove();
	});
	$('#' + id + ' .dialog-box').animate({'opacity':0}, 200, function() { 
		$('#' + id).remove();
		o.onClose.apply();
	});
};

// Alerts
var alertType = {
	error: 0,
	ok: 1,
	info: 2,
	warn: 3
};

var alertTimer = {};

jQuery.alert = function(opt) {
	var defaults = {
		id: 'alert-' + new Date().getTime(),
		parent: 'body',
		title: i18n.General.textDismiss,
		text: '',
		type: alertType.ok,
		position: 'top',
		css: {},
		autoFadeOut: true,
	};
	
	var o = $.extend(defaults, opt);
	var id = o.id;
	var txt = o.text;
	var p = o.parent;
	var wnd = p=='window';
	var type = o.type;
	var css = o.css;
	var pos = o.position;
	var fadeOut = o.autoFadeOut;
	var Alert = '#'+id;
	
	if(fadeOut) {
		if(alertTimer[Alert] != undefined) {
			clearTimeout(alertTimer[Alert]['timer']);
		}
		alertTimer[Alert] = {};
	}
	if($(Alert).length>0) { // Remove existing alert if same id exists
		$(Alert).stop().remove();
	}
	var alertTypes = ['alert_error', 'alert_ok', 'alert_info', 'alert_warn'];
	var alertWrapper = $('<div>').attr({id: id, title: o.title}).addClass('alert').addClass(alertTypes[type]);
	var alertIcon = $('<div>').addClass('alert_icon');
	var alertContent = $('<div>').html(o.text).addClass('alert_content');
	alertWrapper.append(alertIcon).append(alertContent);
	
	$('body').prepend(alertWrapper);
	var width = $(Alert).width()+2;
	var height = $(Alert).height();
	var fullWidth = $(Alert).outerWidth();
	var fullHeight = $(Alert).outerHeight();
	$(Alert).remove();
	$(p).prepend(alertWrapper);
	if(css['width'] == undefined) {
		$(Alert).width(width);
	}
	
	for(var i in css) { 
		$(Alert).css(i, css[i]);
		if(i=='top' || i=='left' || i=='right' || i=='bottom') {
			pos = false;
		}
	}
	
	if(pos != false && p!='body') {
		var offsetWidth = 0;
		var offsetHeight = 0;
		var pWidth = $(p).width();
		
		offsetHeight = fullHeight + 10;
		offsetWidth = (pWidth-fullWidth) / 2;
		
		if(pos=='top') {
			var parentPosition = $(p).css('position');
			if(parentPosition != 'relative' && parentPosition != 'absolute' && parentPosition != 'fixed') {
				$(p).css('position', 'relative');
			}
			$(Alert).css({top: 0-offsetHeight, left: offsetWidth});
		} else if (pos=='center') {
			$(Alert).centerElement({wnd: false});
		}
	} else if(pos != false) {
		$(Alert).centerElement({top: '25%'});
	}
	
	$(Alert).click(function() {
		if(alertTimer[Alert] != undefined) {
			clearTimeout(alertTimer[Alert]['timer']);
			delete alertTimer[Alert];
		}
		$(this).stop().remove();
	});
	
	$(Alert).fadeIn(500, function() {
		if(fadeOut) {
			alertTimer[Alert]['timer'] = setTimeout(function() {
				$(Alert).fadeOut(2000, function() { $(Alert).remove(); });
			}, 2000);
		}
	});
	
	$(Alert).mouseenter(function() {
		if(fadeOut) {
			clearTimeout(alertTimer[Alert]['timer']);
		}
		$(this).stop().css('opacity', 1);
	});
	
	$(Alert).mouseleave(function() {
		if(fadeOut) {
			alertTimer[Alert]['timer'] = setTimeout(function() {
				$(Alert).fadeOut(2000, function() { $(Alert).remove(); });
			}, 2000);
		}
	});
};
// End Alerts