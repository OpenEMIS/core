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
	Autocomplete.init();
});

var Autocomplete = {
	init: function() {
		this.attachAutoComplete('.autocomplete', Autocomplete.select);
	},

	select: function(event, ui) {
		var val = ui.item.value;
		var element;
		for(var i in val) {
			element = $('.' + i);
			
			if (element.length > 0) {
				if(element.get(0).tagName.toUpperCase() === 'INPUT') {
					element.val(val[i]);
				} else {
					element.html(val[i]);
				}
			}
		}
		this.value = ui.item.label;
		return false;
	},
	
	focus: function(event, ui) {
		this.value = ui.item.label;
		event.preventDefault();
	},

	attachAutoComplete: function(element, callback) {
		var url = getRootURL() + $(element).attr('url');
		$(element).autocomplete({
			source: url,
			minLength: 2,
			select: callback,
			focus: Autocomplete.focus
		});
	}
}
