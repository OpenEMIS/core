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
    $("#SearchGuardianName").autocomplete({
        source: getRootURL() + "Students/guardiansAutoComplete",
        minLength: 2,
        select: function(event, ui) {
            $('#SearchGuardianName').val(ui.item.label);
            $('#GuardianFirstName').val(ui.item.first_name);
            $('#GuardianLastName').val(ui.item.last_name);
            $('#GuardianGender').val(ui.item.gender);
            $('#GuardianMobilePhone').val(ui.item.mobile_phone);
            $('#GuardianOfficePhone').val(ui.item.office_phone);
            $('#GuardianHomePhone').val(ui.item.home_phone);
            $('#GuardianAddress').val(ui.item.address);
            $('#GuardianEmail').val(ui.item.email);
            $('#GuardianPostalCode').val(ui.item.postal_code);
            $('#GuardianOccupation').val(ui.item.occupation);
            $('#GuardianGuardianEducationLevelId').val(ui.item.guardian_education_level_id);
            $('#GuardianComments').val(ui.item.comments);
            
            $('#GuardianExistingId').val(ui.item.value);

            $('#guardians form').find("input[type='text']").not('#SearchGuardianName').prop("readonly", true).addClass('readonly');
            $('#guardians form').find('select').not('#StudentGuardianGuardianRelationId').prop("readonly", true).addClass('readonly');
            $('#guardians form').find('textarea').prop("readonly", true).addClass('readonly');
            return false;
        }
    });
});