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
	sms.init();
});

var sms = {
	init: function() {
		sms.attachIconTitle();
	},
	
	attachIconTitle: function() {
		try{
		$('div[title]').tooltip({position: 'top center', effect: 'slide'});

		}catch(e){}
	},


	confirmModifySmsMessage: function(obj) {
		var href = $(obj).attr('href');
		if($(obj).prop('tagName') !== 'A') {
			href = getRootURL() + href;
		}
		var btn = {
			value: i18n.General.textContinue,
			callback: function() { window.location.href = href; }
		};
		
		var dlgOpt = {	
			id: 'delete-dialog',
			title: i18n.General.textWarningConfirmation,
			content: i18n.SMS.confirmModifySmsMessageContent,
			buttons: [btn]
		};
		
		$.dialog(dlgOpt);
		return false;
	}
};
