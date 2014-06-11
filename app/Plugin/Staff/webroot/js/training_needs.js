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
    objTrainingNeeds.init();
});

var objTrainingNeeds = {
    init: function() {
        objTrainingNeeds.getDetailsAfterChangeCourse($("#StaffTrainingNeedRefCourseId"));
    },
    getTrainingNeedTypeSelection: function(obj){
        if($(obj).val()== "1"){
            $('.divCourse').removeClass('hide');
            $('.divNeed').addClass('hide');
        }else{
            $('.divNeed').removeClass('hide');
            $('.divCourse').addClass('hide');
        }
    },
    getDetailsAfterChangeCourse: function(obj){
        var trainingCourseId = $(obj).val();
        var title = $('.ref_course_title');
        var code = $('.ref_course_code');
        var description = $('.ref_course_description');
        var requirement = $('.ref_course_requirement');
        if(trainingCourseId === ""){
            title.val("");
            code.val("");
            description.val("");
            requirement.val("");
        }else{
            $.ajax({ 
                type: "get",
                dataType: "json",
                url: getRootURL()+"Staff/getTrainingCoursesById/"+trainingCourseId+"/1",
                success: function(data){
                    var newTitle = '';
                    var newCode = '';
                    var newDescription = '';
                    var newRequirement = '';
                    
                    if(data == null){
                        return;
                    }
                    
                    $.each(data, function(i,v){
                        newTitle = v.TrainingCourse.title;
                        newCode = v.TrainingCourse.code;
                        newDescription = v.TrainingCourse.description;
                        newRequirement = v.TrainingRequirement.name;
                    });

                    title.val(newTitle);
                    code.val(newCode);
                    description.val(newDescription);
                    requirement.val(newRequirement);
                }
            });
        }
    }
}