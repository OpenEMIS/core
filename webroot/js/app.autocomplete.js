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
	loadingWrapper: '',
	loadingImg: '',
	noDataMsg: '',
	hasDataMsg: '',
	uiItems: {},

	init: function() {
		this.attachAutoComplete('.autocomplete', Autocomplete.select);
		loadingWrapper = $('.loadingWrapper');
		loadingImg = loadingWrapper.find('.img');
		noDataMsg = loadingWrapper.find('.msg');
		hasDataMsg = loadingWrapper.find('.hasDataMsg');
	},

	keyup: function() {
		var val = Autocomplete.uiItems;
		for(var i in val) {
			target = $("input[autocomplete='"+i+"']");
			if( typeof target !== 'string' ){
				if(target.get(0).tagName.toUpperCase() === 'INPUT') {
					target.val('');
				} else {
					target.html('');
				}
			}
		}
	},
	
	select: function(event, ui) {
		var val = ui.item.value;
		for(var i in val) {
			element = $("input[autocomplete='"+i+"']");
			
			if (element.length > 0) {
				if(element.get(0).tagName.toUpperCase() === 'INPUT') {
					element.val(val[i]);
				} else {
					element.html(val[i]);
				}
			}
		}
		this.value = ui.item.label;
		Autocomplete.uiItems = val;
		return false;
	},
	
	focus: function( event, ui ) {
		this.value = ui.item.label;
		Autocomplete.select(event, ui);
		event.preventDefault();
	},
			
	searchComplete: function( event, ui ) {
		if(loadingImg.length === 1){
			loadingImg.hide();
			var recordsCount = ui.content.length;
			if(recordsCount === 0){
				noDataMsg.show();
			} else {
				hasDataMsg.show();
			}
		}
	},
			
	beforeSearch: function( event, ui ) {
		if(loadingImg.length === 1){
			var errorMessage = loadingWrapper.closest('.form-group').children('.error-message');
			if(errorMessage.length > 0){
				errorMessage.remove();
			}
			noDataMsg.hide();
			hasDataMsg.hide();
			loadingWrapper.show();
			loadingImg.show();
		}
	},

	attachAutoComplete: function( element, callback ) {
		var url = getRootURL() + $(element).attr('url');
		var length = $(element).attr('length');
		
		if (length === undefined) {
			length = 2;
		}
		
		$(element).autocomplete({
			source: url,
			minLength: length,
			select: callback,
			focus: Autocomplete.focus,
			response: Autocomplete.searchComplete,
			search: Autocomplete.beforeSearch
		}).on( 'keyup', Autocomplete.keyup );
	},
			
	submitForm: function(obj){
		var parentForm = $(obj).closest('form');
		if(parentForm.length > 0){
			var indicatorField = '<input type="hidden" name="data[new]" value="" />';
			parentForm.append(indicatorField);
			parentForm.find('input.btn_save').click();
			return false;
		}
	}
}
