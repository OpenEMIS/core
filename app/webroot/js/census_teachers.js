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
	CensusTeachers.init();
});

var CensusTeachers = {
	init: function() {
		$('#add_multi_teacher').click(Census.addMultiGradeRow);
	},
    decimalCheck: function(evt,decimalplaces) {
        var keynum = utility.getKeyPressed(evt);
        valueOfKeyPress=keynum-48;

        if ((keynum ==46) && ($(evt.target).val().indexOf('.') != -1) ){
            return false;
        }

        if (valueOfKeyPress >= 0){
            var newProjectedValue = $(evt.target).val() + "" + valueOfKeyPress;
            var pattern = new RegExp("^[0-9]+\.{0,1}[0-9]{0,"+decimalplaces+"}$");
            if (!pattern.test(newProjectedValue)) {return false; }
        }
        return ((keynum >= 48 && keynum <= 57) || keynum < 32 || keynum==undefined || keynum == 46);
    },

    clearBlank: function(obj){
        var row = $(obj).closest('.table_row');
        var type = $(obj).attr('computeType');
        row.find('[computeType="' + type + '"]').each(function() {
            if($(this).val()=='0.') {
                $(this).val(0);
            }
        });
    },

    computeSubtotal: function(obj) {
        var table = $(obj).closest('.table_body');
        var row = $(obj).closest('.table_row');
        var type = $(obj).attr('computeType');
        var subtotal = 0;

        row.find('[computeType="' + type + '"]').each(function() {
            if($(this).val().isEmpty()||$(this).val()=='.'||$(this).val()=='0.0'||$(this).val()=='00') {
                if($(this).attr('allowNull')==undefined) {
                    $(this).val(0);
                    subtotal += parseFloat($(this).val());
                }
            } else {
                subtotal += parseFloat($(this).val());
            }
        });
        if(subtotal>0){
            subtotal = subtotal.toFixed(1);
        }
        row.find('.cell_subtotal').html(subtotal);

        var total = 0;
        table.find('.cell_subtotal').each(function() {
            total += parseFloat($(this).html());
        });
        if(total>0){
            total = total.toFixed(1);
        }
        table.siblings('.table_foot').find('.' + type).html(total);
    }
};
