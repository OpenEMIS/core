<?php
namespace App\Model\Traits;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

trait MessagesTrait {
    public $messages = [
        'Areas' => [
            'noAccessToAreas' => 'You do not have access to any areas',
            'institution_affected' => 'Institution Affected',
            'security_group_affected' => 'Security Group Affected',
            'missing_area' => 'Missing Area',
            'new_area' => 'New Area',
            'api_invalid' => 'URL or data in URL is invalid.'
        ],
        'Attachments' => [
            'date_on_file' => 'Date On File',
        ],
        'Assessments' => [
            'subjects' => 'Subjects',
            'noSubjects' => 'There is no subject selected',
            'templates' => 'Templates',
            'noTemplates' => 'No Templates',
            'noGrades' => 'No Available Grades',
            'noGradingTypes' => 'You need to configure Grading Types first.',
            'addAssessmentItem' => 'Add Assessment Item',
            'assessmentItems' => 'Assessment Items',
            'assessmentPeriods' => 'Assessment Periods',
            'assessmentGradingType' => 'Assessment Grading Type',
            'educationSubject' => 'Education Subject',
            'subjectWeight' => 'Subject Weight',
            'periodWeight' => 'Period Weight'
        ],
        'CustomGroups' => [
            'custom_modules' => 'Module'
        ],
        'date' => [
            'start' => 'Start Date',
            'end' => 'End Date',
            'from' => 'From',
            'to' => 'To'
        ],
        'gender' => [
            'm' => 'Male',
            'f' => 'Female'
        ],
        'general' => [
            'notExists' => 'The record does not exist.',
            'notEditable' => 'This record is not editable',
            'notConfigured' => 'Not Configured',
            'unassigned' => 'Unassigned',
            'exists' => 'The record exists in the system.',
            'noData' => 'There are no records.',
            'noRecords' => 'No Record',
            'select' => [
                'noOptions' => 'No options'
            ],
            'error' => 'An unexpected error has been encounted. Please contact the administrator for assistance.',
            'add' => [
                'success' => 'The record has been added successfully.',
                'failed' => 'The record is not added due to errors encountered.',
                'label' => 'Add',
            ],
            'edit' => [
                'success' => 'The record has been updated successfully.',
                'failed' => 'The record is not updated due to errors encountered.',
                'label' => 'Edit',
            ],
            'delete' => [
                'restrictDelete' => 'The record cannot be deleted.',
                'restrictDeleteBecauseAssociation' => 'Delete operation is not allowed as there are other information linked to this record.',
                'success' => 'The record has been deleted successfully.',
                'failed' => 'The record is not deleted due to errors encountered.',
                'label' => 'Delete',
            ],
            'deleteTransfer' =>[
                'restrictDelete' => 'The record cannot be deleted as there are still records associated with it.'
            ],
            'view' => [
                'label' => 'View',
            ],
            'duplicate' => [
                'success' => 'The record has been duplicated successfully.',
                'failed' => 'The record is not duplicated due to errors encountered.',
            ],
            'reconfirm' => 'Please review the information before proceeding with the operation',
            'academicPeriod' => [
                'notEditable' => 'The chosen academic period is not editable',
            ],
            'invalidTime' => 'You have entered an invalid time.',
            'invalidDate' => 'You have entered an invalid date.',
            'invalidUrl' => 'You have entered an invalid URL.',
            'notSelected' => 'No Record has been selected / saved.',
            'order' => 'Order',
            'visible' => 'Visible',
            'name' => 'Name',
            'description' => 'Description',
            'default' => 'Default',
            'reject' => 'Reject',
            'noClasses' => 'No Classes',
            'noSubjects' => 'No Subjects',
            'noStaff' => 'No Staff',
            'type' => 'Type',
            'amount' => 'Amount',
            'total' => 'Total',
            'notTransferrable' => 'No other alternative options available to convert records.',
            'validationRules' => 'Validation Rules',
            'currentNotDeletable' => 'This record cannot be deleted because it is set as Current',
            'custom_validation_pattern' => 'Please enter a valid format'
        ],
        'fileUpload' => [
            'single' => '*File size should not be larger than 2MB.',
            'multi' => '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.',
            '1' => 'The uploaded file exceeds the max filesize upload limits.',
            '2' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            '3' => 'The uploaded file was only partially uploaded.',
            '4' => 'No file was uploaded.',
            '6' => 'Missing a temporary folder. Please contact your network administrator for assistance.',
            '7' => 'Failed to write file to disk. Please contact your network administrator for assistance.',
            '8' => 'A PHP extension stopped the file upload. Please contact your network administrator for assistance.'
        ],
        'InstitutionInfrastructures' => [
            'ownerAddNotAllowed' => 'You are not allowed to add infrastructure as there are no shifts configured in the current academic period',
            'occupierAddNotAllowed' => 'You are not allowed to add infrastructure as an occupier',
            'occupierEditNotAllowed' => 'You are not allowed to edit infrastructure as an occupier',
            'occupierDeleteNotAllowed' => 'You are not allowed to delete infrastructure as an occupier'
        ],
        'InfrastructureTypes' => [
            'noLevels' => 'No Available Levels',
            'infrastructure_level_id' => 'Level Name'
        ],
        'InstitutionRooms' => [
            'noRooms' => 'No Room found',
            'in_use' => [
                'restrictEdit' => 'Edit operation is not allowed as there are other information linked to this record.',
                'restrictDelete' => 'Delete operation is not allowed as there are other information linked to this record.'
            ],
            'end_of_usage' => [
                'restrictEdit' => 'Edit operation is not allowed as the record already End of Usage.',
                'restrictDelete' => 'Delete operation is not allowed as the record already End of Usage.'
            ],
            'change_in_room_type' => [
                'restrictEdit' => 'Not allowed to change room type in the same day.'
            ],
            'select_subject' => 'Select Subject'
        ],
        'InfrastructureCustomFields' => [
            'infrastructure_level_id' => 'Level Name'
        ],
        'Institutions' => [
            'noClassRecords' => 'There are no available Classes',
            'date_opened' => 'Date Opened',
            'date_closed' => 'Date Closed',
            'noClasses' => 'No Available Classes'
        ],
        'InstitutionStaff' => [
            'title' => 'Staff',
            'start_date' => 'Start Date',
            'fte' => 'FTE',
            'total_fte' => 'Total FTE',
        ],
        'InstitutionPositions' => [
            'current_staff_list' => 'Current Staff List',
            'past_staff_list' => 'Past Staff List',
        ],
        'InstitutionGrades' => [
            'noEducationLevels' => 'There are no available Education Level.',
            'noEducationProgrammes' => 'There are no available Education Programme.',
            'noEducationGrades' => 'There are no available Education Grade.',
            'noGradeSelected' => 'No Education Grade was selected.',
            'failedSavingGrades' => 'Failed to save grades',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'education_grade' => 'Education Grades',
            'allGradesAlreadyAdded' => 'All possible Education Grades for the selected Education Programme already added.'
        ],
        'InstitutionShifts' => [
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'our_shifts' => 'Our Shifts',
            'external_shifts' => 'External Shifts',
            'location' => 'Location',
            'institution' => 'Institution',
            'occupier' => 'Occupier',
            'institution' => 'Institution',
            'allShiftsUsed' => 'All shifts has been used for the selected academic period.',
            'replicateShifts' => 'Should the system replicate the existing shifts for the latest academic period?.',
            'replicateShiftsSuccess' => 'Shifts has been successfully replicated.',
            'replicateShiftsNotChosen' => 'Replication was not chosen, please setup the shifts manually.',
            'noAccessToShift' => 'You do not have access to the shift'
        ],
        'InstitutionClasses' => [
            'expiredGrade' => 'Expired Grade',
            'noClasses' => 'No Classes',
            'students' => 'Students',
            'education_programme' => 'Education Programme',
            'education_grade' => 'Education Grade',
            'staff_id' => 'Home Room Teacher',
            'class' => 'Class',
            'single_grade_field' => 'Single Grade Classes',
            'multi_grade_field' => 'Class Grades',
            'emptyName' => 'Class name should not be empty',
            'emptySecurityUserId' => 'Home Room Teacher should not be empty',
            'emptyNameSecurityUserId' => 'Class name and Home Room Teacher should not be empty',
            'emptySecurityUserIdName' => 'Class name and Home Room Teacher should not be empty',
            'stopDeleteWhenStudentExists' => 'Delete is not allowed as students still exists in class',
            'maximumStudentsReached' => 'Reached the maximum number of students allowed in a class',
            'education_grade_options_empty' => 'No available Grades for the selected period',
            'noTeacherAssigned' => 'No Teacher Assigned',
            'selectTeacherOrLeaveBlank' => 'Select Teacher or Leave Blank',
            'singleGrade' => 'Single Grade',
            'multiGrade' => 'Multi Grade',
            'noShift' => 'There are no shifts configured for the selected academic period'
        ],
        'InstitutionSubjects' => [
            'noGrades' => 'No Grades Assigned',
            'noClasses' => 'No Classes',
            'noSubjects' => 'No Subjects',
            'subjects' => 'Subjects',
            'noPeriods' => 'No Available Periods',
            'education_subject' => 'Subject',
            'class' => 'Subject',
            'teacher' => 'Teacher',
            'students' => 'Students',
            'teachers' => 'Teachers',
            'teacherOrTeachers' => 'Teacher(s)',
            'studentRemovedFromInstitution' => 'This student was removed from the institution earlier',
            'staffRemovedFromInstitution' => 'This staff was removed from the institution earlier',
            'noSubjects' => 'There are no available Education Subjects',
            'allSubjectsAlreadyAdded' => 'All Subjects for the assigned grade already added previously',
            'noSubjectsInClass' => 'There are no subjects in the assigned grade',
            'noSubjectSelected' => 'There is no subject selected',
            'noProgrammes' => 'There is no programme set for available Academic Period on this institution'
        ],
        'InstitutionFees' => [
            'fee_types' => 'Fee Types',
            'noProgrammeGradeFees' => 'No Programme Grade Fees',
            'fee_payments_exists' => 'Unable to delete this record due to payments by students already exists'
        ],
        'Students' => [
            'noGrades' => 'No Grades',
            'noStudents' => 'No Student found'
        ],
        'StudentFees' => [
            'totalAmountExceeded' => 'Total Amount Exceeded Outstanding Amount',
            'payment_date' => 'Payment Date',
            'created_user_id' => 'Created By',
            'comments' => 'Comments',
            'amount' => 'Amount',
            'noStudentFees' => 'No Student Fees',
        ],
        // 'InstitutionStaffAbsences' => [
        //  'first_date_absent' => 'First Day Of Absence',
        //  'last_date_absent' => 'Last Day Of Absence'
        // ],
        'InstitutionAssessments' => [
            'noAssessments' => 'No Assessments',
            'noClasses' => 'No Classes'
        ],
        'InstitutionSurveys' => [
            'save' => [
                'draft' => 'Survey record has been saved to draft successfully.',
                'final' => 'Survey record has been submitted successfully.'
            ],
            'reject' => [
                'success' => 'The record has been rejected successfully.',
                'failed' => 'The record is not rejected due to errors encountered.'
            ],
            'class' => 'Class',
            'noAccess' => 'You do not have access to this Class.'
        ],
        'InstitutionRubrics' => [
            'noRubrics' => 'No Available Rubrics'
        ],
        'InstitutionRubricAnswers' => [
            'rubric_template' => 'Rubric Template',
            'rubric_section_id' => 'Section',
            'noSection' => 'There is no rubric section selected',
            'save' => [
                'draft' => 'Rubric record has been saved to draft successfully.',
                'final' => 'Rubric record has been submitted successfully.',
                'failed' => 'This rubric record is not submitted due to criteria answers is not complete.'
            ],
            'reject' => [
                'success' => 'The record has been rejected successfully.',
                'failed' => 'The record is not rejected due to errors encountered.'
            ]
        ],
        'Surveys' => [
            'noSurveys' => 'No Available Surveys',
        ],
        'StudentSurveys' => [
            'noSurveys' => 'No Surveys',
            'save' => [
                'draft' => 'Survey record has been saved to draft successfully.',
                'final' => 'Survey record has been submitted successfully.'
            ]
        ],
        'password'=> [
            'oldPassword' => 'Current Password',
            'retypePassword' => 'Retype New Password',
        ],
        'EducationGrades' => [
            'add_subject' => 'Add Subject',
        ],
        'RubricCriterias' => [
            //'rubric_section_id' => 'Rubric Section',
            'criterias' => 'Criterias'
        ],
        'RubricTemplateOptions' => [
            'weighting' => 'Weighting'
        ],
        'IdentityTypes' => [
            'deleteDefault' => 'Please set other identity type as default before deleting the current one'
        ],
        'security' => [
            'login' => [
                'fail' => 'You have entered an invalid username or password.',
                'inactive' => 'Your account has been disabled.',
                'remoteFail' => 'Remote authentication failed, please try local login.',
            ],
            'noAccess' => 'You do not have access to this location.',
            'emptyFields' => 'Some of the required fields for this authentication type are empty.'
        ],
        'ExternalDataSource' => [
            'emptyFields' => 'Some of the required fields for this external datasource type are empty.'
        ],
        'SecurityRoles' => [
            'userRoles' => 'User Roles',
            'systemRoles' => 'System Roles'
        ],
        'StudentAttendances' => [
            'noClasses' => 'No Available Classes',
            'lateTime' => 'Late time should not be earlier than start time.'
        ],
        'InstitutionStudentAbsences' => [
            'noClasses' => 'No Available Classes',
            'noStudents' => 'No Available Students',
            'notEnrolled' => 'Not able to add absence record as this student is no longer enrolled in the institution.',
        ],
        'StaffAttendances' => [
            'noStaff' => 'No Available Staff',
            'lateTime' => 'Late time should not be earlier than start time.'
        ],
        'StaffAbsences' => [
            'noStaff' => 'No Available Staff',
            'noShift' => 'There are no shifts configured for the selected academic period, will be using system configuration timing.'
        ],
        'StaffBehaviours' => [
            'date_of_behaviour' => 'Date',
            'time_of_behaviour' => 'Time'
        ],
        'SystemGroups' => [
            'tabTitle' => 'System Groups'
        ],
        'SystemRoles' => [
            'tabTitle' => 'System Roles'
        ],
        'SurveyTemplates' => [
            'survey_module_id' => 'Module'
        ],
        'SurveyQuestions' => [
            'survey_template_id' => 'Survey Template'
        ],
        'SurveyStatuses' => [
            'survey_template_id' => 'Survey Template'
        ],
        'SurveyForms' => [
            'add_question' => 'Add Question',
            'add_to_section' => 'Add to Section',
            'notSupport' => 'Not supported in this form.'
        ],
        'StaffPositionTitles' => [
            'inProgress' => 'Update of staff position title roles is in process, please try again later.',
            'error' => 'There is an error in the update of the title record, please try again later.'
        ],
        'time' => [
            'start' => 'Start Time',
            'end' => 'End Time',
            'from' => 'From',
            'to' => 'To'
        ],
        'Users' => [
            'student_category' => 'Category',
            'status' => 'Status',
            'select_student' => 'Select Student',
            'select_student_empty' => 'No Other Student Available',
            'add_all_student' => 'Add All Students',
            'add_student' => 'Add Student',
            'select_staff' => 'Select Staff',
            'add_staff' => 'Add Staff',
            'select_teacher' => 'Select Teacher',
            'select_room' => 'Select Room',
            'add_teacher' => 'Add Teacher'
        ],
        'UserGroups' => [
            'tabTitle' => 'User Groups'
        ],
        'Workflows' => [
            'restrictDelete' => 'Delete operation is not allowed as this record is required by system.',
            'noWorkflows' => 'You need to configure Workflows for this form.',
            'workflow_model_id' => 'Form'
        ],
        'WorkflowSteps' => [
            'notCategorized' => 'Not Categorized',
            'systemDefined' => 'This is a system defined record',
            'restrictDelete' => 'Delete operation is not allowed as this is a system defined record.'
        ],
        'WorkflowActions' => [
            'add_event' => 'Add Event',
            'restrictDelete' => 'Delete operation is not allowed as this is a system defined record.'
        ],
        'WorkflowStatuses' => [
            'noSteps' => 'No Available Workflow Steps'
        ],
        'InstitutionQualityVisits' => [
            'noPeriods' => 'No Available Periods',
            'noClasses' => 'No Available Classes',
            'noSubjects' => 'No Available Subjects',
            'noStaff' => 'No Available Staff'
        ],
        'StudentBehaviours' => [
            'noClasses' => 'No Classes',
            'noStudents' => 'No Students'
        ],
        'TransferRequests' => [
            'request' => 'Transfer request has been submitted successfully.',
            'enrolled' => 'This student has already been enrolled in an institution.',
            'hasDropoutApplication' => 'There is a pending dropout application for this student at the moment, please reject the dropout application before making another request.'
        ],
        'TransferApprovals' => [
            'existsInNewSchool' => 'Student is already exists in the new school',
            'enrolledInInstitution' => 'Student is already enrolled in another school.',
            'approve' => 'Transfer request has been approved successfully.',
            'reject' => 'Transfer request has been rejected successfully.'
        ],
        'StudentPromotion' => [
            'noGrades' => 'No Available Grades',
            'noStudents' => 'No Available Students',
            'noPeriods' => 'You need to configure Academic Periods for Promotion / Graduation',
            'noData' => 'There are no available Students for Promotion / Graduation',
            'current_period' => 'Current Academic Period',
            'next_period' => 'Next Academic Period',
            'success' => 'Students have been promoted',
            'noNextGrade' => 'Next grade in the Education Structure is not available in this Institution',
            'reconfirm' => 'Please review the information before proceeding with the operation',
            'noStudentSelected' => 'There are no students selected',
            'noAvailableGrades' => 'No Available Grades in this Institution',
            'noAvailableAcademicPeriod' => 'No Available Academic Periods',
            'noNextGradeOrNextPeriod' => 'Next grade in the Education Structure is not available in this Institution or no Next Academic Period defined',
            'savingPromotionError' => 'Some selected students record were not updated succesfully',
            'successGraduated' => 'Students have graduated',
            'successOthers' => 'Students status changed successfully',
            'noNextAcademicPeriod' => 'There is no next academic period for the promotion.',
            'pendingRequest' => 'There is a pending student status change request at the moment.',
            'selectNextGrade' => 'Please select a grade to promote to.'
        ],
        'IndividualPromotion' => [
            'noGrades' => 'No Available Grades',
            'noPeriods' => 'You need to configure Academic Periods for Promotion / Graduation',
            'success' => 'Students status changed successfully.',
            'noNextGrade' => 'Next grade in the Education Structure is not available in this Institution',
            'reconfirm' => 'Please review the information before proceeding with the operation',
            'noAvailableGrades' => 'No Available Grades in this Institution',
            'noAvailableAcademicPeriod' => 'No Available Academic Periods',
            'noNextGradeOrNextPeriod' => 'Next grade in the Education Structure is not available in this Institution or no Next Academic Period defined',
            'savingPromotionError' => 'The student record was not updated succesfully',
            'noNextAcademicPeriod' => 'There is no next academic period for the promotion.',
            'pendingTransfer' => 'There is a pending transfer request for this student.',
            'pendingDropout' => 'There is a pending dropout request for this student.',
        ],
        'StudentTransfer' => [
            'noGrades' => 'No Available Grades',
            'noStudents' => 'No Available Students',
            'noInstitutions' => 'No Available Institutions',
            'noData' => 'There are no available Students for Transfer.',
            'success' => 'The selected students are pending for transfer approval.'
        ],
        'StaffPositionProfiles' => [
            'request' => 'Request for Change in Assignment has been submitted successfully.',
            'notExists' => 'Staff record no longer exists in the system.',
            'errorApproval' => 'Record cannot be approved due to errors encountered.',
        ],
        'StaffTransferRequests' => [
            'alreadyAssigned' => '%s is currently assigned to %s',
            'confirmRequest' => 'By clicking save, a transfer request will be sent to the institution for approval',
            'errorApproval' => 'Record cannot be assigned due to errors encountered',
        ],
        'StaffTransferApprovals' => [
            'transferType' => 'Please select the transfer type.',
            'effectiveDate' => 'Please enter an effective date for the partial transfer.',
            'newFTE' => 'Please select a new FTE for the partial transfer.',
            'staffEndOfAssignment' => 'The assignment of this staff has ended.'
        ],
        'UndoStudentStatus' => [
            'noGrades' => 'No Available Grades',
            'noStudents' => 'No Available Students',
            'noData' => 'There are no available Students for revert Student Status.',
            'reconfirm' => 'Please review the information before proceeding with the operation.',
            'notUndo' => 'Not available to revert.',
            'success' => 'Student records have been reverted successfully.',
            'failed' => 'Failed to revert student records.'
        ],
        'EducationProgrammes' => [
            'add_next_programme' => 'Add Next Programme'
        ],
        'StudentAdmission' => [
            'noClass' => 'No Class Selected',
            'existsInSchool' => 'Student is already exists in the school',
            'enrolledInInstitution' => 'Student is already enrolled in another school.',
            'existsInRecord' => 'Student has already been added to admission list',
            'approve' => 'Student admission has been approved successfully.',
            'reject' => 'Student admission has been rejected successfully.'
        ],
        'DropoutRequests' => [
            'request' => 'Dropout request hsa been submitted successfully.',
            'notEligible' =>  'This student is not eligible for this action. Please reject this request.'
        ],
        'StudentDropout' => [
            'exists' => 'Student has already dropped out from the school.',
            'approve' => 'Dropout request has been approved successfully.',
            'reject' => 'Dropout request has been rejected successfully.',
            'hasTransferApplication' => 'There is a pending transfer application for this student at the moment, please remove the transfer application before making another request.'
        ],
        'Import' => [
            'total_rows' => 'Total Rows:',
            'rows_imported' => 'Rows Imported:',
            'rows_updated' => 'Rows Updated:',
            'rows_failed' => 'Rows Failed:',
            'download_failed_records' => 'Download Failed Records',
            'download_passed_records' => 'Download Successful Records',
            'row_number' => 'Row Number',
            'error_message' => 'Error Message',
            'invalid_code' => 'Invalid Code',
            'duplicate_code' => 'Duplicate Code Identified',
            'duplicate_openemis_no' => 'Duplicate OpenEMIS ID Identified',
            'duplicate_unique_key' => 'Duplicate Unique Key on the same sheet',
            'validation_failed' => 'Failed Validation',
            'file_required' => 'File is required',
            'not_supported_format' => 'File format not supported',
            'over_max' => 'File records exceeds maximum size allowed',
            'wrong_template' => 'Wrong template file',
            'execution_time' => 'Execution Time',
            'over_max_rows' => 'File records exceeds maximum rows allowed',
            'the_file' => 'The file',
            'success' => 'is successfully imported.',
            'failed' => 'failed to import.',
            'partial_failed' => 'failed to import completely.',
            'survey_code_not_found' => 'Survey code is missing from the file. Please make sure that survey code exists on sheet "References" cell B4.',
            'survey_not_found' => 'No identifiable survey found',
            'no_answers' => 'No record were found in the file imported',
            'institution_network_connectivity_id' => 'code'
        ],
        'TrainingSessions' => [
            'trainer_type' => 'Type',
            'trainer' => 'Trainer'
        ],
        'TrainingSessionResults' => [
            'noResultTypes' => 'You need to configure Result Types under Training Course.',
            'noTrainees' => 'No Available Trainees'
        ],
        'StaffTrainingApplications' => [
            'success' => 'This session has been added successfully.',
            'fail' => 'Failed to add the session.',
            'exists' => 'This session has already been added.'
        ],
        'CustomForms' => [
            'notSupport' => 'Not supported in this form.'
        ],
        'ExaminationStudents' => [
            'restrictAdd' => 'Add operation is not allowed.'
        ],
        'ExaminationNotRegisteredStudents' => [
            'restrictAdd' => 'Add operation is not allowed.'
        ],
        'InstitutionExaminationsUndoRegistration' => [
            'success' => 'Undo of student examination registration is successful.',
            'fail' => 'Undo of student examination registration is successful.',
            'noStudentSelected' => 'There are no students selected',
        ],
        'InstitutionExaminationStudents' => [
            'notAvailableForRegistration' => 'Not available for registration',
            'noStudentSelected' => 'There are no students selected',
        ],

        'ExaminationCentreStudents' => [
            'notAvailableForRegistration' => 'Not available for registration',
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'This student is not assigned to a room as there are no available rooms.',
        ],

        'LinkedInstitutionAddStudents' => [
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'Not all students are assigned to a room, please manually assigned the students to the room.'
        ],

        'BulkStudentRegistration' => [
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'Not all students are assigned to a room, please manually assigned the students to the room.'
        ],

        // Validation Messages
        'Institution' => [
            'Institutions' => [
                'academicPeriod' => 'There are no Academic Periods in the system.',
                'noActiveInstitution' => 'There is no active institution',
                'noSubjectsInClass' => 'There are no subjects in the assigned grade',
                'noSubjectSelected' => 'There is no subject selected',
                'noProgrammes' => 'There is no programme set for this institution',
                'noSections' => 'There is no class under the selected academic period',
                'date_closed' => [
                    'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened'
                ],
                'email' => [
                    'ruleValidEmail' => 'Please enter a valid Email',
                    'ruleUnique' => 'Email already exists in the system'
                ],
                'longitude' => [
                    'ruleLongitude' => 'Please enter a valid Longitude'
                ],
                'latitude' => [
                    'ruleLatitude' => 'Please enter a valid Latitude'
                ],
                'area_id' => [
                    'ruleAuthorisedArea' => 'You have not been authorised to add an institution into that area.'
                ],
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'institution_provider_id' => [
                    'ruleLinkedSector' => 'Please select a provider linked to the chosen Sector'
                ]
            ],
            'InstitutionContacts' => [
                'email' => [
                    'ruleValidEmail' => 'Please enter a valid Email'
                ]
            ],
            'InstitutionClasses' => [
                'noGrade' => 'There is no grade selected',
                'emptyName' => 'Class name should not be empty',
                'emptySecurityUserId' => 'Home Room Teacher should not be empty',
                'emptyNameSecurityUserId' => 'Class name and Home Room Teacher should not be empty',
                'name' => [
                    'ruleUniqueNamePerAcademicPeriod' => 'Class name has to be unique',
                ],
            ],

            'InstitutionProgrammes' => [
                'education_programme_id' => [
                    'unique' => 'This Education Programme already exists in the system'
                ],
                'noGrade' => 'There is no grade selected',
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'InstitutionGrades' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ],
                'start_date' => [
                    'ruleCompareWithInstitutionDateOpened' => 'Start Date should not be earlier than Institution Date Opened'
                ]
            ],
            'Absences' => [
                'start_date' => [
                    'ruleNoOverlappingAbsenceDate' => 'Absence is already added for this date and time.',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'timeRangeHint' => 'Time should be between %s and %s'
            ],
            'StaffAbsences' => [
                'end_time' => [
                    'ruleCompareAbsenceTimeReverse' => 'End Time should not be earlier than Start Time'
                ]
            ],
            'StaffLeave' => [
                'date_to' => [
                    'ruleCompareDateReverse' => 'Date To should not be earlier than Date From'
                ]
            ],
            'InstitutionStudentAbsences' => [
                'end_time' => [
                    'ruleCompareAbsenceTimeReverse' => 'End Time should not be earlier than Start Time'
                ]
            ],
            'InstitutionStudents' => [
                'academicPeriod' => 'You need to configure Academic Periods first.',
                'educationProgrammeId' => 'You need to configure Education Programmes first.',
                'institutionGrades' => 'You need to configure Institution Grades first.',
                'classes' => 'You need to configure Classes first.',
                'sections' => 'You need to configure Classes first.',
                'studentStatusId' => 'You need to configure Student Statuses first.',
                'deleteNotEnrolled' => 'You cannot remove a not enrolled student from the institution.',
                'notInSchool' => '<Not In School>'
            ],
            'InstitutionStaff' => [
                'institutionPositionId' => 'You need to configure Institution Site Positions first.',
                'securityRoleId' => 'You need to configure Security Roles first.',
                'FTE' => 'There are no available FTE for this position.',
                'noAvailableFTE' => 'No availabe FTE',
                'noFTE' => 'New staff is not added to the institutition as there are no available FTE for the selected position.',
                'noInstitutionPosition' => 'There are no position available.',
                'staffTypeId' => 'You need to configure Staff Types first.',
                'error' => 'New staff is not added to the institutition, due to an error',
            ],
            'InstitutionPositions' => [
                'position_no' => [
                    'ruleUnique' => 'The position number that you have entered already existed, please try again.',
                    'ruleNoSpaces' => 'Only alphabets and numbers are allowed'
                ]
            ],
            'InstitutionShifts' => [
                'institution_name' => [
                    'ruleCheckLocationInstitutionId' => 'Please select an institution location.'
                ],
                'start_time' => [
                    'ruleCheckShiftAvailable' => 'Shift timing is not available.'
                ]
            ],
            'StudentGuardians' => [
                'guardianRelationId' => 'You need to configure Guardian Relations first.',
                'guardianEducationLevel' => 'You need to configure Guardian Education Level first.'
            ],
            'StaffPositions' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'StaffPositionProfiles' => [
                'institution_position_id' => [
                    'ruleCheckFTE' => 'No available FTE.',
                ],
                'start_date' => [
                    'ruleStaffExistWithinPeriod' => 'The staff has already exist within the start date and end date specified.',
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End date should not be earlier than Start date'
                ],
            ],
            'TransferRequests' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ],
                'student_id' => [
                    'ruleNoNewDropoutRequestInGradeAndInstitution' => 'There is a pending dropout application for this student at the moment, please reject the dropout application before making another request.',
                    'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                        'inTargetSchool' => 'Student is already enrolled in this school.',
                        'inAnotherSchool' => 'Student is already enrolled in another school.',
                    ],
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                ]
            ],
            'IndividualPromotion' => [
                'effective_date' => [
                    'ruleInAcademicPeriod' => 'Please select a date within the chosen Academic Period'
                ]
            ],
            'VisitRequests' => [
                'date_of_visit' => [
                    'ruleDateWithinAcademicPeriod' => 'Please select a date within the chosen Academic Period'
                ]
            ],
            'Students' => [
                'student_name' => [
                    'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                        'inTargetSchool' => 'Student is already enrolled in this school.',
                        'inAnotherSchool' => 'Student is already enrolled in another school.',
                    ],
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                    'ruleCheckAdmissionAgeWithEducationCycleGrade' => 'This student does not fall within the allowed age range for this grade',
                    'ageHint' => 'The student should be %s years old',
                    'ageRangeHint' => 'The student should be between %s to %s years old',
                    'ruleStudentEnrolledInOthers' => 'Student has already been enrolled in another Institution.'
                ],
                'class' => [
                    'ruleClassMaxLimit' => 'Reached the maximum number of students allowed in a class.'
                ],
            ],
            'Staff' => [
                'staff_name' => [
                    'ruleInstitutionStaffId' => 'Staff has already been added.'
                ],
                'institution_position_id' => [
                    'ruleCheckFTE' => 'No available FTE.',
                ],
                'start_date' => [
                    'ruleStaffExistWithinPeriod' => 'The staff has already exist within the start date and end date specified.',
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End date should not be earlier than Start date'
                ],
            ],
            'StudentAdmission' => [
                'student_name' => [
                    'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                        'inTargetSchool' => 'Student is already enrolled in this school.',
                        'inAnotherSchool' => 'Student is already enrolled in another school.',
                    ],
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                    'ruleCheckAdmissionAgeWithEducationCycleGrade' => 'This student does not fall within the allowed age range for this grade',
                    'ageHint' => 'The student should be %s years old',
                    'ageRangeHint' => 'The student should be between %s to %s years old',
                    'ruleStudentEnrolledInOthers' => 'Student has already been enrolled in another Institution.',
                    'ruleCheckPendingAdmissionExist' => 'Student has already been added to admission list'
                ],
                'class' => [
                    'ruleClassMaxLimit' => 'Reached the maximum number of students allowed in a class.'
                ],
            ],
            'InstitutionFeeTypes' => [
                'amount' => [
                    'ruleMaxLength' => 'Amount entered exceeds system limit'
                ],
            ],
            'InstitutionInfrastructures' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ]
            ],
            'InstitutionRooms' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'end_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.',
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ],
                'new_start_date' => [
                    'ruleCompareDateReverse' => 'New Start Date should not be earlier than or same as Start Date'
                ]
            ],
            'DropoutRequests' => [
                'effective_date' => [
                    'ruleDateAfterEnrollment' => 'Effective Date cannot be earlier than the Enrollment Date'
                ]
            ],
            'StudentDropout' => [
                'effective_date' => [
                    'ruleDateAfterEnrollment' => 'Effective Date cannot be earlier than the Enrollment Date'
                ]
            ],
            'InstitutionExaminationStudents' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ]
            ]
        ],
        'User' => [
            'Users' => [
                'first_name' => [
                    'ruleNotBlank' => 'Please enter a valid First Name',
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid First Name'
                ],
                'middle_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Middle Name'
                ],
                'third_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Third Name'
                ],
                'last_name' => [
                    'ruleNotBlank' => 'Please enter a valid Last Name',
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Last Name'
                ],
                'preferred_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Preferred Name'
                ],
                'openemis_no' => [
                    'ruleNotBlank' => 'Please enter a valid OpenEMIS ID',
                    'ruleUnique' => 'Please enter a unique OpenEMIS ID'
                ],
                'gender_id' => [
                    'ruleNotBlank' => 'Please select a Gender'
                ],
                'address' => [
                    'ruleNotBlank' => 'Please enter a valid Address'
                ],
                'date_of_birth' => [
                    'ruleNotBlank' => 'Please select a Date of Birth',
                    'ruleCompare' => 'Please select a Date of Birth',
                    'ruleCompare' => 'Date of Birth cannot be future date',
                    'ruleValidDate' => 'You have entered an invalid date.'
                ],
                'username' => [
                    'ruleNotBlank' => 'Please enter a valid username',
                    'ruleNoSpaces' => 'Only alphabets and numbers are allowed',
                    'ruleUnique' => 'This username is already in use.',
                    'ruleCheckUsername' => 'Invalid username. Usernames must contain only alphabets and/or digits. Username can also be a valid email',
                    'ruleMinLength' => 'Username must be at least 6 characters',
                ],
                'password' => [
                    'ruleChangePassword' => 'Incorrect password.',
                    'ruleCheckUsernameExists' => 'Please enter a valid password',
                    'ruleMinLength' => 'Password must be at least 6 characters',
                    'ruleNoSpaces' => 'Password should not contain spaces',
                    'ruleCheckNumberExists' => 'Password should contain at least 1 number',
                    'ruleCheckUppercaseExists' => 'Password should contain at least 1 uppercase character',
                    'ruleCheckLowercaseExists' => 'Password should contain at least 1 lowercase character',
                    'ruleCheckNonAlphaExists' => 'Password should contain at least 1 non-alphanumeric character',
                    'ruleCheckLength' => 'Password length should be between %s to %s',
                ],
                'retype_password' => [
                    'ruleChangePassword' => 'Please confirm your new password',
                    'ruleCompare' => 'Both passwords do not match'
                ],
                'newPassword' => [
                    'ruleChangePassword' => 'Please enter your new password',
                    'ruleMinLength' => 'Password must be at least 6 characters'
                ],
                'retypeNewPassword' => [
                    'ruleChangePassword' => 'Please confirm your new password',
                    'ruleCompare' => 'Both passwords do not match'
                ],
                 'photo_content' => [
                    'ruleCheckSelectedFileAsImage' => 'Please upload image format files. Eg. jpg, png, gif.',
                ]
            ],
            'Accounts' => [
                'username' => [
                    'ruleNotBlank' => 'Please enter a valid username',
                    'ruleNoSpaces' => 'Only alphabets and numbers are allowed',
                    'ruleUnique' => 'This username is already in use.',
                    'ruleCheckUsername' => 'Invalid username. Usernames must contain only alphabets and/or digits. Username can also be a valid email',
                    'ruleMinLength' => 'Username must be at least 6 characters',
                ],
                'password' => [
                    'ruleChangePassword' => 'Incorrect password.',
                    'ruleCheckUsernameExists' => 'Please enter a valid password',
                    'ruleMinLength' => 'Password must be at least 6 characters',
                    'ruleNoSpaces' => 'Password should not contain spaces'
                ],
                'retype_password' => [
                    'ruleChangePassword' => 'Please confirm your new password',
                    'ruleCompare' => 'Both passwords do not match'
                ],
                'newPassword' => [
                    'ruleChangePassword' => 'Please enter your new password',
                    'ruleMinLength' => 'Password must be at least 6 characters'
                ],
                'retypeNewPassword' => [
                    'ruleChangePassword' => 'Please confirm your new password',
                    'ruleCompare' => 'Both passwords do not match'
                ]
            ],
            'Contacts' => [
                'contact_type_id' => [
                    'ruleNotBlank' => 'Please enter a Contact Type'
                ],
                'value' => [
                    'ruleNotBlank' => 'Please enter a valid value',
                    'ruleValidateNumeric' => 'Please enter a valid Numeric value',
                    'ruleValidateEmail' => 'Please enter a valid Email',
                    'ruleValidateEmergency' => 'Please enter a valid Value',
                ],
                'preferred' => [
                    'ruleValidatePreferred' => 'Please select a preferred contact type'
                ],
            ],
            'Identities' => [
                'identity_type_id' => [
                    'ruleNotBlank' => 'Please select a Type'
                ],
                'issue_location' => [
                    'ruleNotBlank' => 'Please enter a valid Issue Location'
                ],
                'issue_date' => [
                    'comparison' => 'Issue Date Should be Earlier Than Expiry Date'
                ],
                'expiry_date' => [
                    'ruleNotBlank' => 'Expiry Date Is Required'
                ],
                'number' => [
                    'ruleUnique' => 'This identity has already existed in the system.',
                    'custom_validation' => 'Please enter a valid Identity Number'
                ],
            ],
            'Languages' => [
                'language_id' => [
                    'ruleNotBlank' => 'Please select a Language'
                ],
                'listening' => [
                    'ruleNotBlank' => 'Please enter a number between 0 and 5'
                ],
                'speaking' => [
                    'ruleNotBlank' => 'Please enter a number between 0 and 5'
                ],
                'reading' => [
                    'ruleNotBlank' => 'Please enter a number between 0 and 5'
                ],
                'writing' => [
                    'ruleNotBlank' => 'Please enter a number between 0 and 5'
                ],
            ],
            'Comments' => [
                'title' => [
                    'ruleNotBlank' => 'Please enter a valid Title'
                ],
                'comment' => [
                    'ruleNotBlank' => 'Please enter a valid Comment'
                ],
            ],
            'SpecialNeeds' => [
                'special_need_type_id' => [
                    'ruleNotBlank' => 'Please select a valid Special Need Type.'
                ]
            ],
            'Awards' => [
                'award' => [
                    'ruleNotBlank' => 'Please enter a valid Award.'
                ],
                'issuer' => [
                    'ruleNotBlank' => 'Please enter a valid Issuer.'
                ]
            ],
            'Attachments' => [
                'date_on_file' => 'Date On File',
                'name' => [
                    'ruleNotBlank' => 'Please enter a File name'
                ]
            ],
            'BankAccounts' => [
                'account_name' => [
                    'ruleNotBlank' => 'Please enter an Account name'
                ],
                'account_number' => [
                    'ruleNotBlank' => 'Please enter an Account number'
                ],
                'bank_id' => [
                    'ruleNotBlank' => 'Please select a Bank'
                ],
                'bank_branch_id' => [
                    'ruleNotBlank' => 'Please select a Bank Branch'
                ]
            ],
        ],
        'Student' => [
            'Extracurriculars' => [
                'name' => [
                    'ruleNotBlank' => 'Please enter a valid Title.'
                ],
                'hours' => [
                    'ruleNotBlank' => 'Please enter a valid Hours.'
                ],
                'start_date' => [
                    'ruleCompareDate' => 'Start Date cannot be later than End Date',
                ]
            ],
            'Guardians' => [
                'guardian_id' => [
                    'ruleStudentGuardianId' => 'This guardian has already added.'
                ]
            ],
            'Students' => [
                'first_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid First Name'
                ],
                'middle_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Middle Name'
                ],
                'third_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Third Name'
                ],
                'last_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Last Name'
                ],
                'preferred_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Preferred Name'
                ],
                'openemis_no' => [
                    'ruleNotBlank' => 'Please enter a valid OpenEMIS ID',
                    'ruleUnique' => 'Please enter a unique OpenEMIS ID'
                ],
                'date_of_birth' => [
                    'ruleNotBlank' => 'Please select a Date of Birth',
                    'ruleCompare' => 'Date of Birth cannot be future date',
                    'ruleValidDate' => 'You have entered an invalid date.'
                ],
            ],
        ],
        'Staff' => [
            'transferExists' => 'There is an existing transfer request for that staff.',
            'date_of_birth' => 'Date Of Birth',
            'photo_content' => 'Profile Image',
            'Qualifications' => [
                'qualification_title' => [
                    'required' => 'Please enter a valid Qualification Title'
                ],
                'graduate_year' => [
                    'required' => 'Please enter a valid Graduate Year',
                    'ruleNumeric' => 'Please enter a valid Numeric value',
                ],
                'qualification_level_id' => [
                    'required' => 'Please enter a valid Qualification Level'
                ],
                'qualification_specialisation_id' => [
                    'required' => 'Please enter a valid Major/Specialisation'
                ],
                'qualification_institution_name' => [
                    'validHiddenId' => 'Please enter a valid Institution'
                ]
            ],
            'Staff' => [
                'first_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid First Name'
                ],
                'middle_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Middle Name'
                ],
                'third_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Third Name'
                ],
                'last_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Last Name'
                ],
                'preferred_name' => [
                    'ruleCheckIfStringGotNoNumber' => 'Please enter a valid Preferred Name'
                ],
                'openemis_no' => [
                    'ruleNotBlank' => 'Please enter a valid OpenEMIS ID',
                    'ruleUnique' => 'Please enter a unique OpenEMIS ID'
                ],
                'date_of_birth' => [
                    'ruleNotBlank' => 'Please select a Date of Birth',
                    'ruleCompare' => 'Date of Birth cannot be future date',
                    'ruleValidDate' => 'You have entered an invalid date.'
                ],
            ],
            'Extracurriculars' => [
                'name' => [
                    'ruleNotBlank' => 'Please enter a valid Title.'
                ],
                'hours' => [
                    'ruleNotBlank' => 'Please enter a valid Hours.'
                ],
                'start_date' => [
                    'ruleCompareDate' => 'Start Date cannot be later than End Date',
                ]
            ],
            'Employments' => [
                'employment_type_id' => [
                    'ruleNotBlank' => 'Please select a Type'
                ],
                'employment_date' => [
                    'ruleNotBlank' => 'Please enter a valid Date'
                ]
            ],
            'Salaries' => [
                'salary_date' => [
                    'ruleNotBlank' => 'Please select a Salary Date'
                ],
                'gross_salary' => [
                    'ruleNotBlank' => 'Please enter a valid Gross Salary'
                ],
                'net_salary' => [
                    'ruleNotBlank' => 'Please enter a valid Net Salary'
                ]
            ],
            'Memberships' => [
                'membership' => [
                    'ruleNotBlank' => 'Please enter a valid Membership.'
                ],
                'issue_date' => [
                    'ruleCompareDate' => 'Issue Date cannot be later than Expiry Date',
                ]
            ],
            'Licenses' => [
                'license_type_id' => [
                    'ruleNotBlank' => 'Please select a valid License Type.'
                ],
                'issuer' => [
                    'ruleNotBlank' => 'Please enter a valid Issuer.'
                ],
                'license_number' => [
                    'ruleNotBlank' => 'Please enter a valid License Number.'
                ],
                'issue_date' => [
                    'ruleCompareDate' => 'Issue Date cannot be later than Expiry Date',
                ]
            ],
            'TrainingNeeds' => [
                'course_code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ],
                'course_id' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'Achievements' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'Salaries' => [
                'gross_salary' => [
                    'ruleMoney' => 'Please enter a valid amount.'
                ],
                'net_salary' => [
                    'ruleMoney' => 'Please enter a valid amount.'
                ]
            ],
            'Appraisal' => [
                'competencies_goals' => 'Competencies / Goals',
                'rating' => 'Rating',
                'value' => 'Value',
                'final_rating' => 'Final Rating',
                'deleted_competencies' => 'This competency has been removed'
            ],
        ],
        'AcademicPeriod' => [
            'AcademicPeriods' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ],
                'current' => [
                    'ruleValidateNeeded' => 'Academic Period needs to be set as current'
                ]
            ]
        ],
        'Localization' => [
            'Translations' => [
                'en' => [
                    'ruleUnique' => 'This translation is already exists'
                ]
            ]
        ],
        'Translations' => [
            'success' => 'The language has been successfully compiled.',
            'failed' => 'The language has not been compiled due to errors encountered.',
        ],
        'Security' => [
            'SecurityRoles' => [
                'name' => [
                    'ruleUnique' => 'This role name already exists in the system'
                ],
            ],
            'Users' => [
                'username' => [
                    'ruleUnique' => 'This username is already in use',
                    'ruleMinLength' => 'Username must be at least 6 characters',
                ]
            ]
        ],
        'Labels' => [
            'code' => [
                'ruleUnique' => 'This code already exists in the system'
            ]
        ],
        'Training' => [
            'TrainingCourses' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'TrainingSessions' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ]
        ],
        'Workflow' => [
            'Workflows' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'WorkflowActions' => [
                'event_key' => [
                    'ruleUnique' => 'This event has already been assigned.'
                ]
            ]
        ],
        'Health' => [
            'Medications' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ]
        ],
        'Report' => [
            'InstitutionStudentsOutOfSchool' => [
                'reportName' => 'Students Out of School'
            ]
        ],
        'CustomField' => [
            'text' => [
                'minLength' => 'Text should be at least %d characters',
                'maxLength' => 'Text should not be exceed %d characters',
                'range' => 'Text should be between %d and %d characters'
            ],
            'number' => [
                'minValue' => 'Number should not be lesser than %d',
                'maxValue' => 'Number should not be greater than %d',
                'range' => 'Number should be between %d and %d'
            ],
            'date' => [
                'earlier' => 'Date should be earlier than or equal to %s',
                'later' => 'Date should be later than or equal to %s',
                'between' => 'Date should be between %s and %s (inclusive)'
            ],
            'time' => [
                'earlier' => 'Time should be earlier than or equal to %s',
                'later' => 'Time should be later than or equal to %s',
                'between' => 'Time should be between %s and %s (inclusive)'
            ],
            'file' => [
                'maxSize' => 'File size should not be more than %s'
            ]
        ],
        'Assessment' => [
            'Assessments' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form',
                    'ruleAssessmentExistByGradeAcademicPeriod' => 'Assessment already created for the selected grade.'
                ],
                'education_grade_id' => [
                    'ruleAssessmentExistByGradeAcademicPeriod' => 'Assessment already created for the selected grade.'
                ]
            ],
            'AssessmentPeriods' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form',
                    'ruleUniqueCodeByForeignKeyAcademicPeriod' => 'Code must be unique for the same academic period'
                ],
                'start_date' => [
                    'ruleInParentAcademicPeriod' => 'Date must be within selected academic period start and end date',
                ],
                'end_date' => [
                    'ruleInParentAcademicPeriod' => 'Date must be within selected academic period start and end date',
                ],
                'weight' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleWeightRange' => 'Value must be positive and less than 2.0'
                ],
            ],
            'AssessmentItems' => [
                'weight' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleWeightRange' => 'Value must be positive and less than 2.0'
                ],
            ],
            'AssessmentGradingTypes' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                ],
                'pass_mark' => [
                    'ruleNotMoreThanMax' => 'Min value cannot be more than max value',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ],
                'max' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ],
            ],
            'GradingOptions' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form',
                ],
                'min' => [
                    'ruleNotMoreThanMax' => 'Min value cannot be more than max value',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ],
                'max' => [
                    'ruleNotMoreThanGradingTypeMax' => 'Grading Option max value cannot be more than Grading Type max value',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ],
            ],
        ],
        'StaffClasses' => [
            'notActiveHomeroomTeacher' => 'Not active homeroom teacher'
        ],
        'StaffSubjects' => [
            'notActiveTeachingStaff' => 'Not active teaching staff'
        ],
        'Examination' => [
            'Examinations' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period',
                ]
            ],
            'ExaminationCentres' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code for this examination centre in this examination'
                ],
            ],
            'ExaminationCentreRooms' => [
                'name' => [
                    'ruleUnique' => 'Please enter a unique name for this examination centre room'
                ],
                'size' => [
                    'ruleValidateNumeric' => 'Please enter a valid Numeric value'
                ],
                'number_of_seats' => [
                    'ruleValidateNumeric' => 'Please enter a valid Numeric value',
                    'ruleExceedRoomCapacity' => 'Number of student exceeds the total number of seats available'
                ]
            ],
            'ExaminationItems' => [
                'weight' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleWeightRange' => 'Value must be positive and less than 2.0'
                ]
            ],
            'ExaminationGradingTypes' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                ],
                'pass_mark' => [
                    'ruleNotMoreThanMax' => 'Pass mark cannot be more than Max mark',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' =>'Mark entered exceeds system limit'
                ],
                'max' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ]
            ],
            'GradingOptions' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form',
                ],
                'min' => [
                    'ruleNotMoreThanMax' => 'Min value cannot be more than max value',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ],
                'max' => [
                    'ruleNotMoreThanGradingTypeMax' => 'Grading Option max value cannot be more than Grading Type max value',
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ]
            ],
            'ExaminationCentreStudents' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ],
                'student_id' => [
                    'ruleUnique' => 'This student is already registered to the selected exam',
                    'ruleNotInvigilator' => 'This student is an invigilator in this examination'
                ]
            ],
            'BulkStudentRegistration' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ]
            ],
            'LinkedInstitutionAddStudents' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ]
            ]
        ],
        'Indexes' => [
            'TableHeader' => [
                'Criteria',
                'Operator',
                'Threshold',
                'Index'
            ]
        ],
        'Configuration' => [
        	'ConfigProductLists' => [
        		'name' => [
        			'ruleUnique' => 'This product already exists in the system',
        		],
        		'url' => [
        			'invalidUrl' => 'You have entered an invalid URL.',
        		]
        	],
            'ConfigAdministrativeBoundaries' => [
                'name' => [
                    'ruleUnique' => 'This product already exists in the system',
                ],
                'value' => [
                    'invalidUrl' => 'You have entered an invalid URL.',
                    'ruleValidateJsonAPI' => 'URL or data in URL is invalid.'
                ]
            ]
        ]
    ];


    public function getMessage($code, $options = []) {
        $sprintf = (array_key_exists('sprintf', $options))? $options['sprintf']: [];
        $defaultMessage = (array_key_exists('defaultMessage', $options))? $options['defaultMessage']: true;

        $Labels = TableRegistry::get('Labels');
        $message = Cache::read($code, $Labels->getDefaultConfig());

        if ($message == false) {
            $message = $this->messages;
            $index = explode('.', $code);
            foreach ($index as $i) {
                if (isset($message[$i])) {
                    $message = $message[$i];
                } else {
                    if (!$defaultMessage) return false;
                    $message = '[Message Not Found]';
                    break;
                }
            }
        }

        return !is_array($message) ? __(vsprintf($message, $sprintf)) : $message;
    }
}
