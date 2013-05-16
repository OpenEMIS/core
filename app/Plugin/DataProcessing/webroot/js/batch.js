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

var checked = true;
function toggleSelect() {
	if(checked) {
		$('.indicators:checked').removeAttr('checked');
		$('#toggleBtn').val('Select All');
		checked = false;
	} else {
		$('.indicators:not(:disabled)').attr('checked', 'checked');
		$('#toggleBtn').val('Clear All');
		checked = true;
	}
}

var batch = {
	indicatorBase: getRootURL() + 'Batch/Indicator/',
	
	runBatch: function() {
		var format = $('#format').val();
		var list = $.map($('.indicators:checked'), function(e, i) {
			return +e.value;
		});
		
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: this.indicatorBase + 'runBatch',
			data: {
				format: format,
				list: list
			},
			beforeSend: function(jqXHR) {
				$.mask({parent: '#indicator', text: i18n.Batch.textRunning});
			},
			success: function(data, textStatus) {
				$.unmask(function() {
					if(data.type == ajaxType.success) {
						$.dialog({title: 'Batch Result', content: i18n.Batch.textExecuteSuccess});
					}
				});
			}
		});
	},
	
	listLogs: function() {
		var dlgOpt = {
			title: 'Batch Logs',
			ajaxUrl: this.indicatorBase + 'listLogs',
			width: 300
		};
		
		$.dialog(dlgOpt);
	}
}