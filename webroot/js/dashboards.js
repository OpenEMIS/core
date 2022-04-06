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
    localStorage.removeItem('academic_period_id');
    localStorage.removeItem('academic_period_id_new');
    dashboards.init();
    showInstituteProfileCompleteData();
    showProfileCompleteData();
});

var dashboards = {

    init: function() {
        $.each($('.highchart'), function(key, group) {
            json = $(group).html();
            obj = JSON.parse(json);
            $(group).highcharts(obj);
            $(group).css({
                "visibility": "visible"
            });
        });
        $('#dashboard-spinner').css({
            "display": "none"
        });
    }
}

function showInstituteProfileCompleteData() {
    $("#institute_profile_detail").click(function() {
        $("#profile-data-div").toggle();
        $(this).text($(this).text() == 'Details' ? 'Hide Details' : 'Details');
    });
}

function showProfileCompleteData() {
    $("#profile_detail").click(function() {
        $("#profile_data_div").toggle();
        $(this).text($(this).text() == 'Details' ? 'Hide Details' : 'Details');
    });
}