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
    objStaffAwards.init();
});

var objStaffAwards = {

    init: function() {
        var elementAward = '#searchAward';
        var elementIssuer = '#searchIssuer';
        var table = $('#award');
        var selectAwardUrl = getRootURL() + table.attr('selectAwardUrl');alert(selectAwardUrl);
        objStaffAwards.attachAutoComplete(elementAward, selectAwardUrl + '1/', objStaffAwards.selectAwardField);
        objStaffAwards.attachAutoComplete(elementIssuer, selectAwardUrl + '2/', objStaffAwards.selectIssuerField);
    },

    selectAwardField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchAward') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },

    selectIssuerField: function(event, ui) {
        var val = ui.item.value;
        var element;
        for(var i in val) {
            element = $('.' + i);
            console.log(element.get(0));
            if(element.get(0).tagName.toUpperCase() === 'INPUT' && element.get(0).id == 'searchIssuer') {
                element.val(val[i]);
            } else {
                element.html(val[i]);
            }
        }
        return false;
    },
    

    attachAutoComplete: function(element, url, callback) {
        $(element).autocomplete({
            source: url,
            minLength: 2,
            select: callback
        });
    }

}
