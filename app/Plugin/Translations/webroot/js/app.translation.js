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

var Translation = {
	compileFile: function(obj) {
		
		var compileBtn = {
			value: i18n.Translation.textCompile,
			callback: function() {
				
				var maskId;
				var url = getRootURL() + $(obj).attr('url');
				var lang = $("#language").val();
				
				$.ajax({
					type: 'POST',
					url: url,
					data: {lang: lang},
					beforeSend: function(jqXHR) {
						maskId = $.mask({parent: '.content_wrapper', text: i18n.Translation.textCompiling});
					},
					success: function(data, textStatus) {
						var callback = function() {
							$.closeDialog({id:'compile-dialog',onClose:function() {
								window.location.href = getRootURL() + 'Translations';

                            }});
                           
						};
						$.unmask({id: maskId, callback: callback});
					}
				});
			}
		};

		var dlgOpt = {
			id: 'compile-dialog',
			title: i18n.Translation.textTranslationCompileTitle + ' - ' +$("#language option:selected").text(),
			content: i18n.Translation.contentTranslationCompile,
			buttons: [compileBtn],
		};
		$.dialog(dlgOpt);
	}
};
