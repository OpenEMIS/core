<?php
namespace App\Model\Traits;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

trait MessagesTrait
{
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
            'periodWeight' => 'Period Weight',
            'classification' => 'Classification',
            'academic_term' => 'Please check the academic terms to ensure that all the values are entered.'
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
            'noFile' => 'File does not exist.',
            'failConnectToExternalSource' => 'There is an issue establishing connection to the External Datasource. Please contact the administrator for assistance.',
            'notExistsInExternalSource' => 'The record does not exist in the External Datasource. Please contact the administrator for assistance.',
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
                'cascadeDelete' => 'All associated information related to this record will also be removed.',
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
            'uniqueCodeForm' => 'Code must be unique from other codes in this form',
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
            'noSurveys' => 'No Surveys',
            'noStaff' => 'No Staff',
            'type' => 'Type',
            'amount' => 'Amount',
            'total' => 'Total',
            'notTransferrable' => 'No other alternative options available to convert records.',
            'validationRules' => 'Validation Rules',
            'currentNotDeletable' => 'This record cannot be deleted because it is set as Current',
            'custom_validation_pattern' => 'Please enter a valid format',
            'inactive_message' => 'This institution is inactive, all data entry operation are disabled.',
            'status_update' => 'By Saving this Page, the institution status will be updated.',
            'contactInstitution' => [
                    'telephone' => 'Telephone cannot be empty for Exam Centres to be set into Institutions->Contacts->Institution',
                    'fax' => 'Fax cannot be empty for Exam Centres to be set into Institutions->Contacts->Institution',
                    'both' => 'Telephone & Fax cannot be empty for Exam Centres to be set into Institutions->Contacts->Institution'
                ]
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
            'occupierDeleteNotAllowed' => 'You are not allowed to delete infrastructure as an occupier',
            'accessibilityOption' => 'Designed for use by anyone including those with special needs/disabilities.',
            'effectiveDate' => 'Date should be within Academic Period.'
        ],
        'InfrastructureTypes' => [
            'noLevels' => 'No Available Levels',
            'infrastructure_level_id' => 'Level Name'
        ],
        'InstitutionLands' => [
            'noLand' => 'No Land found',
            'in_use' => [
                'restrictEdit' => 'Edit operation is not allowed as there are other information linked to this record.',
                'restrictDelete' => 'Delete operation is not allowed as there are other information linked to this record.'
            ],
            'end_of_usage' => [
                'restrictEdit' => 'Edit operation is not allowed as the record already End of Usage.',
                'restrictDelete' => 'Delete operation is not allowed as the record already End of Usage.'
            ],
            'change_in_land_type' => [
                'restrictEdit' => 'Not allowed to change land type in the same day.'
            ]
        ],
        'InstitutionBuildings' => [
            'noLand' => 'No Building found',
            'in_use' => [
                'restrictEdit' => 'Edit operation is not allowed as there are other information linked to this record.',
                'restrictDelete' => 'Delete operation is not allowed as there are other information linked to this record.'
            ],
            'end_of_usage' => [
                'restrictEdit' => 'Edit operation is not allowed as the record already End of Usage.',
                'restrictDelete' => 'Delete operation is not allowed as the record already End of Usage.'
            ],
            'change_in_building_type' => [
                'restrictEdit' => 'Not allowed to change building type in the same day.'
            ]
        ],
        'InstitutionFloors' => [
            'noFloors' => 'No Floor found',
            'in_use' => [
                'restrictEdit' => 'Edit operation is not allowed as there are other information linked to this record.',
                'restrictDelete' => 'Delete operation is not allowed as there are other information linked to this record.'
            ],
            'end_of_usage' => [
                'restrictEdit' => 'Edit operation is not allowed as the record already End of Usage.',
                'restrictDelete' => 'Delete operation is not allowed as the record already End of Usage.'
            ],
            'change_in_floor_type' => [
                'restrictEdit' => 'Not allowed to change floor type in the same day.'
            ]
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
            'noInstitution' => 'Please populate the following information to create your institution.',
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
            'gradesAlreadyAdded' => 'Selected Education Grade for the selected Education Programme already added.',
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
            'classes_secondary_staff' => 'Secondary Teacher',
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
        'InstitutionStudentIndexes' => [
            'noClasses' => 'No Classes',
            'noStudents' => 'No Students'
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
            'noAccess' => 'You do not have access to this Class.',
            'mandatoryFieldFill' => "Please fill up mandatory fields before submitting for approval"
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
        'EducationGradesSubjects' => [
            'tooltip_message' => 'If this option is set to Yes, students will be allocated automatically to this subject upon enrolment to a class',
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
                'changePassword' => 'This is the first time that you are logging in, please change your password.'
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
            'noReasons' => 'You need to configure Student Absence Reasons first.',
            'lateTime' => 'Late time should not be earlier than start time.'
        ],
        'InstitutionStudentAbsences' => [
            'noClasses' => 'No Available Classes',
            'noStudents' => 'No Available Students',
            'notEnrolled' => 'Not able to add absence record as this student is no longer enrolled in the institution.',
        ],
        'StaffAttendances' => [
            'noStaff' => 'No Available Staff',
            'noReasons' => 'You need to configure Staff Absence Reasons first.',
            'lateTime' => 'Late time should not be earlier than start time.'
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
            'notSupport' => 'Not supported in this form.',
            'restrictEditFilters' => 'You are not allowed to remove the following filters: %s'
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
            'add_teacher' => 'Add Teacher',
            'tooltip_message_password' => 'The password is automatically generated by the system',
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
        'WorkflowStepsParams' => [
            'institutionOwner' => 'The selected institution can execute workflow actions on this step',
            'institutionVisible' => 'The selected institutions can view this step'
        ],
        'WorkflowActions' => [
            'add_event' => 'Add Event',
            'restrictDelete' => 'Delete operation is not allowed as this is a system defined record.',
            'no_two_post_event' => 'Only one post event for each action is allowed.'
        ],
        'WorkflowRules' => [
            'process' => [
                'start' => [
                    'success' => 'The process has been started successfully.',
                    'failed' => 'The process is not started due to errors encountered.'
                ],
                'abort' => [
                    'success' => 'The process has been aborted successfully.',
                    'failed' => 'The process is not aborted due to errors encountered.'
                ]
            ]
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
            'noStudents' => 'No Students',
            'date_of_behaviour' => [
                'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
            ],
        ],
        'StudentPromotion' => [
            'noGrades' => 'No Available Grades',
            'noStudents' => 'No Available Students',
            'noPeriods' => 'You need to configure Academic Periods for Promotion / Graduation',
            'noData' => 'There are no available Students for Promotion / Graduation',
            'current_period' => 'Current Academic Period',
            'next_period' => 'Next Academic Period',
            'success' => 'Students have been promoted',
            'saveDraftSuccess' => 'Student Promotion successfully saved as draft',
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
            'selectNextGrade' => 'Please select a grade to promote to.',
            'notEnrolled' => 'Not enrolled to any grades'
        ],
        'BulkStudentAdmission' => [
            'success' => 'Bulk students admission successful',
            'reconfirm' => 'Please review the information before proceeding with the operation',
            'noStudentSelected' => 'There are no students selected',
            'savingError' => 'Some selected students record were not updated succesfully',
        ],
        'BulkStudentTransferIn' => [
            'success' => 'Bulk students transfer successful',
            'reconfirm' => 'Please review the information before proceeding with the operation',
            'noStudentSelected' => 'There are no students selected',
            'savingError' => 'Some selected students record were not updated succesfully',
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
            'pendingWithdraw' => 'There is a pending withdraw request for this student.',
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
        'WithdrawRequests' => [
            'request' => 'Withdraw request has been submitted successfully.',
            'configureWorkflowStatus' => 'Please configure the steps to the Approved and Pending statuses before adding any withdrawal record.',
            'notEligible' =>  'This student is not eligible for this action. Please reject this request.'
        ],
        'StudentWithdraw' => [
            'exists' => 'Student has already dropped out from the school.',
            'approve' => 'Withdraw request has been approved successfully.',
            'reject' => 'Withdraw request has been rejected successfully.',
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
            'upload_error' => 'The file cannot be imported due to errors encountered.',
            'value_not_in_list' => 'Selected value is not in the list',
            'survey_code_not_found' => 'Survey code is missing from the file. Please make sure that survey code exists on sheet "References" cell B4.',
            'survey_not_found' => 'No identifiable survey found',
            'no_answers' => 'No record were found in the file imported',
            'institution_network_connectivity_id' => 'code',
            'exam_centre_dont_match' => 'Examination and centre combination cannot be found.',
            'identity_type_doesnt_match' => 'Identity type selected must be %s.',
            'identity_number_exist' => 'Identity Number for %s already exists.',
            'identity_type_required' => 'Identity Type cant be empty if Identity Number is specified.',
            'identity_number_required' => 'Identity Number cant be empty if Identity Type is specified.',
            'identity_number_invalid_pattern' => 'Invalid Identity Number pattern.',
            'staff_title_grade_not_match' => 'Selected value does not match with Staff Position Title Type',
            'contact_required' => 'Contact is required',
            'nationality_required' => 'Nationality cant be empty.',
        ],
        'ImportInstitutionSurveys' => [
            'restrictImport' => 'Import operation is not allowed as the record is already Done'
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
            'restrictAdd' => 'Add operation is not allowed.',
            'notAssignedRoom' => 'Not all students are assigned to a room, please manually assign the students to a room.'
        ],
        'ExaminationNotRegisteredStudents' => [
            'restrictAdd' => 'Add operation is not allowed.'
        ],
        'InstitutionExaminations' => [
            'noGrades' => 'There are no available grades set for this institution',
        ],
        'InstitutionExaminationsUndoRegistration' => [
            'success' => 'Undo of student examination registration is successful.',
            'fail' => 'Undo of student examination registration is successful.',
            'noStudentSelected' => 'There are no students selected',
        ],
        'InstitutionExaminationStudents' => [
            'notAvailableForRegistration' => 'Not available for registration',
            'noStudentSelected' => 'There are no students selected',
            'noLinkedExamCentres' => 'Please contact your administrator to set up available Examination Centres for the selected Examination'
        ],
        'AssessmentGradingTypes' => [
            'noGradingOptions' => 'There are no grading options for this grading type'
        ],
        'OutcomeGradingTypes' => [
            'noGradingOptions' => 'There are no grading options for this grading type'
        ],
        'CompetencyGradingTypes' => [
            'noGradingOptions' => 'There are no grading options for this grading type'
        ],
        'Examinations' => [
            'noExaminationItems' => 'There are no examination items for this examination'
        ],
        'ExaminationCentres' => [
            'savingProcessStarted' => 'Examination centres are currently being added in the background'
        ],
        'ExaminationCentresExaminations' => [
            'savingProcessStarted' => 'Examination centres are currently being linked in the background'
        ],
        'ExaminationCentresExaminationsStudents' => [
            'notAvailableForRegistration' => 'Not available for registration',
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'This student is not assigned to a room as there are no available rooms.',
        ],

        'LinkedInstitutionAddStudents' => [
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'Not all students are assigned to a room, please manually assign the students to a room.'
        ],

        'BulkStudentRegistration' => [
            'noStudentSelected' => 'There are no students selected',
            'notAssignedRoom' => 'Not all students are assigned to a room, please manually assign the students to a room.'
        ],
        'ExaminationCentresExaminationsInvigilators' => [
            'noInvigilatorsSelected' => 'There are no invigilators selected'
        ],
        'Textbooks' => [
            'noTextbooks' => 'No Textbooks',
            'noProgrammes' => 'No Programmes',
            'noGrades' => 'No Grades',
            'noClassSubjectSelected' => 'Please select Subject and Textbook before adding record',
            'noTextbookStatusCondition' => 'Please define Textbook Status and Condition before proceed',
            'noTextbookStudent' => 'Please add physical textbook to be added'
        ],
        'InstitutionTextbooks' => [
            'noTextbooks' => 'No Textbooks',
            'noClasses' => 'No Classes',
            'noRecords' => 'No Records'
        ],
        'Templates' => [
            'addSuccess' => 'Competency Template was added successfully, please set up the Competency Items'
        ],
        'Items' => [
            'addSuccess' => 'Competency Item was added successfully, please set up the Competency Criterias'
        ],
        'OutcomeTemplates' => [
            'addSuccess' => 'Outcome Template was added successfully, please set up the Outcome Criterias'
        ],
        'StudentCompetencies' => [
            'noPeriod' => 'No Period',
            'noItem' => 'No Item',
            'noCriterias' => 'Please setup competency criterias for the selected item'
        ],
        'Licenses' => [
            'select_classification' => 'Select Classification'
        ],
        'UserNationalities' => [
            'noRecordRemain' => 'There should be at least one Nationality record'
        ],
        'StudentAbsence' => [
            'deleteRecord' => 'Student absence record deleted successfully'
        ],
        'EducationStructure' => [
            'noGradesSetup' => 'Please set up Education Grades before adding Grade Subjects',
            'noProgrammesSetup' => 'Please set up Education Programmes before adding Grade Subjects'
        ],
        'UserContacts' => [
            'noEmailRemain' => 'There should be at least one Email record'
        ],
        'Reports' => [
            'noWorkflowStatus' => 'You need to configure Workflow Statuses for this Workflow'
        ],
        'ReportCardComments' => [
            'noProgrammes' => 'There is no programme set for this institution'
        ],
        'ReportCardStatuses' => [
            'noProgrammes' => 'There is no programme set for this institution',
            'noTemplate' => 'There is no template for this Report Card. Please contact the administrator for assistance.',
            'noFilesToDownload' => 'There are no generated Report Cards to download',
            'noFilesToPublish' => 'There are no generated Report Cards to publish',
            'noFilesToUnpublish' => 'There are no published Report Cards to unpublish',
            'inProgress' => 'There is already a process running for this Report Card',
            'generate' => 'The Report Card will be generated in the background',
            'generateAll' => 'All Report Cards will be generated in the background',
            'checkReportCardTemplatePeriod' => 'The Report Card period is not active. Please contact the System Administrator.',
            'publish' => 'The Report Card has been successfully published',
            'publishAll' => 'All generated Report Cards have been published successfully',
            'unpublish' => 'The Report Card has been successfully unpublished',
            'unpublishAll' => 'All published Report Cards have been unpublished successfully',
            'email' => 'The Report Card will be sent in the background',
            'emailAll' => 'All Report Cards will be sent in the background',
            'emailInProgress' => 'There is already a email process sending in the background',
			'date_closed' => 'Generate date for report card has been closed'
        ],
        'StaffProfiles' => [
            'noProgrammes' => 'There is no programme set for this institution',
            'noTemplate' => 'There is no template for this Staff Profile. Please contact the administrator for assistance.',
            'noFilesToDownload' => 'There are no generated Staff Profiles to download',
            'noFilesToPublish' => 'There are no generated Staff Profiles to publish',
            'noFilesToUnpublish' => 'There are no published Staff Profiles to unpublish',
            'inProgress' => 'There is already a process running for this Staff Profile',
            'generate' => 'The Staff Profile will be generated in the background',
            'generateAll' => 'All Staff Profile will be generated in the background',
            'checkReportCardTemplatePeriod' => 'The Staff Profile period is not active. Please contact the System Administrator.',
            'publish' => 'The Staff Profile has been successfully published',
            'publishAll' => 'All generated Staff Profiles have been published successfully',
            'unpublish' => 'The Staff Profile has been successfully unpublished',
            'unpublishAll' => 'All published Staff Profiles have been unpublished successfully',
            'email' => 'The Staff Profile will be sent in the background',
            'emailAll' => 'All Staff Profiles will be sent in the background',
            'emailInProgress' => 'There is already a email process sending in the background',
			'date_closed' => 'Generate date for Staff Profile has been closed'
        ],
        'StudentProfiles' => [
            'noProgrammes' => 'There is no programme set for this institution',
            'noTemplate' => 'There is no template for this Student Profile. Please contact the administrator for assistance.',
            'noFilesToDownload' => 'There are no generated Student Profiles to download',
            'noFilesToPublish' => 'There are no generated Student Profiles to publish',
            'noFilesToUnpublish' => 'There are no published Student Profiles to unpublish',
            'inProgress' => 'There is already a process running for this Student Profile',
            'generate' => 'The Student Profile will be generated in the background',
            'generateAll' => 'All Student Profile will be generated in the background',
            'checkReportCardTemplatePeriod' => 'The Student Profile period is not active. Please contact the System Administrator.',
            'publish' => 'The Student Profile has been successfully published',
            'publishAll' => 'All generated Student Profiles have been published successfully',
            'unpublish' => 'The Student Profile has been successfully unpublished',
            'unpublishAll' => 'All published Student Profiles have been unpublished successfully',
            'email' => 'The Student Profile will be sent in the background',
            'emailAll' => 'All Student Profiles will be sent in the background',
            'emailInProgress' => 'There is already a email process sending in the background',
			'date_closed' => 'Generate date for Student Profile has been closed'
        ],
        'RecipientPaymentStructures' => [
            'noApprovedAmount' => 'Please set up Approved Amount for the scholarship'
        ],
        'AlertRules' => [
            'Attendance' => [
                'threshold' => 'Days within 1 to 30'
            ],
            'LicenseRenewal' => [
                'value' => 'Days within %d to %d',
                'hour' => 'Hours within %d to %d '
                    .'<br> Total accumulated hours based on'
                    .'<br> selected field of study within the'
                    .'<br> validity of license'
            ],
            'LicenseValidity' => [
                'value' => 'Days within %d to %d'
            ],
            'RetirementWarning' => [
                'value' => 'Ages within %d to %d'
            ],
            'StaffEmployment' => [
                'value' => 'Days within %d to %d'
            ],
            'StaffLeave' => [
                'value' => 'Days within %d to %d',
                'leavePeriodOverlap' => 'Leave period applied overlaps existing records.',
                'noLeave' => 'Date from is less than joining date of staff.',
                'noLeaveEndDate' => 'Date from is greater than end date of staff.',
                'noLeaveEndDateTo' => 'Date to is greater than end date of staff.'
            ],
            'StaffType' => [
                'value' => 'Days within %d to %d'
            ],
            'ScholarshipApplication' => [
                'value' => 'Days within %d to %d'
            ],
            'ScholarshipDisbursement' => [
                'value' => 'Days within %d to %d'
            ]
        ],
        'UserBodyMasses' => [
            'dateNotWithinPeriod' => 'Date should be within %s and %s'
        ],
        'Calendars' => [
            'dateNotWithinPeriod' => 'Date should be within %s and %s',
            'endDate' => [
                    'compareWithStartDate' => 'End Date should not be earlier than Start Date'
                ]
        ],
        'StaffTransfers' => [
            'restrictStaffTransfer' => 'Transfer is not allowed between different institution type or institution provider.'
        ],
        'StaffTransferOut' => [
            'existingStaffTransfer' => 'There is an existing transfer record for this staff'
        ],
        'StudentTransferOut' => [
            'existingStudentTransfer' => 'There is an existing transfer record for this student',
            'pendingStudentWithdraw' => 'There is a pending withdraw request for this student.',
            'unableToTransfer' => 'Unable to do student transfer due to associated records.'
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
                'date_opened' => [
                    'ruleLessThanToday' => 'Date should not be later than today'
                ],
                'date_closed' => [
                    'ruleCompareDateReverse' => 'Date Closed should not be earlier than Date Opened',
                    'ruleCheckPendingWorkbench' => 'There is still pending item in institution workbench, please clear the workbench before proceed.'
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
                    'ruleAuthorisedArea' => 'You have not been authorised to add an institution into that area.',
                    'configuredArea' => 'Please select area from %s level.'
                ],
                'area_administrative_id' => [
                    'configuredArea' => 'Please select area administrative from %s level.'
                ],
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'institution_provider_id' => [
                    'ruleLinkedSector' => 'Please select a provider linked to the chosen Sector'
                ],
                'classification' => [
                    'validClassification' => 'Please enter a valid Classification',
                ],
            ],
            'InstitutionMaps' => [
                'longitude' => [
                    'ruleLongitude' => 'Please enter a valid Longitude'
                ],
                'latitude' => [
                    'ruleLatitude' => 'Please enter a valid Latitude'
                ]
            ],
            'InstitutionContacts' => [
                'email' => [
                    'ruleValidEmail' => 'Please enter a valid Email'
                ]
            ],
            'InstitutionContactPersons' => [
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
                'staff_id' => [
                    'ruleCheckHomeRoomTeachers' => 'Home Room Teacher and Secondary Teacher cannot be the same person.'
                ],
                'capacity' => [
                    'ruleCheckMaxStudentsPerClass' => 'Capacity must not exceed the maximum number of students per class.'
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
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleCheckStudentInEducationProgrammes' => 'Unable to set the end date because there are students still enrolled in this programme.'
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
            'StaffLeave' => [
                'date_to' => [
                    'ruleCompareDateReverse' => 'Date To should not be earlier than Date From',
                    'ruleInAcademicPeriod' => 'Please select a date within the chosen Academic Period'
                ],
                'date_from' => [
                    'ruleInAcademicPeriod' => 'Please select a date within the chosen Academic Period',
                    'ruleCompareDateReverse' => 'Date To should not be earlier than Date From',
                    'leavePeriodOverlap' => 'Leave period applied overlaps attendace records.'
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
                ],
                'is_homeroom' => [
                    'ruleCheckHomeRoomTeacherAssignments' => 'There are homeroom teachers assigned to Classes',
                    'ruleIsHomeroomEmpty' => 'Please leave this field empty for non-teaching type titles'
                ],
                'status_id' => [
                    'ruleCheckStatusIdValid' => 'Invalid status id'
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
            'InfrastructureNeeds' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'name' => [
                    'ruleUnique' => 'Please enter a unique name'
                ],
                'date_completed' => [
                    'compareWithDateStarted' => 'Date completed should not be earlier than date started'
                ],
            ],
            'InfrastructureProjects' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'name' => [
                    'ruleUnique' => 'Please enter a unique name'
                ],
                'date_completed' => [
                    'compareWithDateStarted' => 'Date completed should not be earlier than date started'
                ],
            ],
            'FeederOutgoingInstitutions' => [
                'institution_id' => [
                    'ruleUnique' => 'Recipient institution must be unique for the same academic period and education grade'
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
                    'ruleStudentEnrolledInOthers' => 'Student has already been enrolled in another Institution.',
                    'studentNotExists' => 'This student does not exist in the system.'
                ],
                'class' => [
                    'ruleClassMaxLimit' => 'Reached the maximum number of students allowed in a class.'
                ],
                'gender_id' => [
                    'compareStudentGenderWithInstitution' => 'The selected institution only accepts %s student.'
                ],
            ],
            'StudentUser' => [
                'start_date' => [
                    'ruleCheckProgrammeEndDateAgainstStudentStartDate' => 'This institution does not offer the selected Education Grade anymore.'
                ],
                'education_grade_id' => [
                    'checkProgrammeEndDate' => 'The institution only offers the selected education grade until %s'
                ],
                'gender_id' => [
                    'compareStudentGenderWithInstitution' => 'The selected institution only accepts %s student.'
                ],
            ],
            'Staff' => [
                'staff_name' => [
                    'ruleInstitutionStaffId' => 'Staff has already been added.'
                ],
                'institution_position_id' => [
                    'ruleCheckFTE' => 'No available FTE.'
                ],
                'staff_assignment' => [
                    'ruleCheckStaffAssignment' => 'The staff has already been assigned to another Institution.'
                ],
                'start_date' => [
                    'ruleStaffExistWithinPeriod' => 'The staff has already exist within the start date and end date specified.',
                    'ruleInAllPeriod' => 'Staff start date must be within all academic period range'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End date should not be earlier than Start date'
                ],
            ],
            'StaffUser' => [
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date is not within the academic period.'
                ]
            ],
            'StudentAdmission' => [
                'academic_period_id' => [
                    'ruleCheckValidAcademicPeriodId' => 'Invalid Academic Period id'
                ],
                'student_id' => [
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                    'ruleCheckPendingAdmissionExist' => 'Student has already been added to admission list'
                ],
                'institution_class_id' => [
                    'ruleCheckValidClassId' => 'This institution does not offer the selected class for this selected education grade in the selected academic period',
                    'ruleClassMaxLimit' => 'Reached the maximum number of students allowed in a class.'
                ],
                'gender_id' => [
                    'compareStudentGenderWithInstitution' => 'The selected institution only accepts %s student.'
                ],
                'start_date' => [
                    'ruleCheckProgrammeEndDateAgainstStudentStartDate' => 'This institution does not offer the selected Education Grade anymore.',
                    'ruleInAcademicPeriod' => 'Date is not within the academic period.'
                ],
                'end_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'education_grade_id' => [
                    'checkProgrammeExist' => 'This insistution does not offer the selected Education Grade',
                    'checkProgrammeEndDate' => 'The institution only offers the selected education grade until %s'
                ],
                'status_id' => [
                    'ruleCheckStatusIdValid' => 'Invalid status id'
                ],
            ],
            'StaffBehaviours' => [
                'date_of_behaviour' => [
                    'ruleInAcademicPeriod' => 'Date is not within the academic period.'
                ]
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
            'InstitutionLands' => [
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
            'InstitutionBuildings' => [
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
            'InstitutionFloors' => [
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
            'WithdrawRequests' => [
                'effective_date' => [
                    'ruleDateAfterEnrollment' => 'Effective Date cannot be earlier than the Enrollment Date'
                ]
            ],
            'StudentWithdraw' => [
                'effective_date' => [
                    'ruleDateAfterEnrollment' => 'Effective Date cannot be earlier than the Enrollment Date'
                ]
            ],
            'InstitutionExaminationStudents' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ]
            ],
            'InstitutionTextbooks' => [
                'code' => [
                    'ruleUnique' => 'Code must be unique for the same academic period',
                ]
            ],
            'InstitutionAssets' => [
                'code' => [
                    'ruleUnique' => 'Code must be unique for the same academic period',
                ]
            ],
            'InstitutionAssessments' => [
                'marks' => [
                    'markHint' => 'Mark should be between %s and %s'
                ],
                'grading_type' => [
                    'notFound' => 'No Grading Type found'
                ]
            ],
            'InstitutionTransportProviders' => [
                'name' => [
                    'ruleUnique' => 'This field has to be unique'
                ]
            ],
            'InstitutionBuses' => [
                'plate_number' => [
                    'ruleUnique' => 'This field has to be unique'
                ],
                'capacity' => [
                    'notZero' => 'Capacity must be more than 0',
                ]
            ],
            'InstitutionTrips' => [
                'name' => [
                    'ruleUnique' => 'This field has to be unique'
                ],
                'days' => [
                    'ruleNotEmpty' => 'This field cannot be left empty'
                ],
                'assigned_students' => [
                    'checkMaxLimit' => 'Total passengers should not be more than bus capacity %d',
                    'busNotFound' => 'Bus record not found',
                    'busCapacityNotSet' => 'There is no capacity configured for the selected bus'
                ]
            ],
            'StaffTransferIn' => [
                'new_start_date' => [
                    'ruleCompareDateReverse' => 'Start Date should not be earlier than Current Institution End Date'
                ],
                'new_end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'StaffTransferOut' => [
                'previous_end_date' => [
                    'ruleCompareDateReverse' => 'Position End Date should not be earlier than Position Start Date'
                ],
                'previous_effective_date' => [
                    'ruleCompareDateReverse' => 'Effective Date should not be earlier than Position Start Date'
                ]
            ],
            'StudentTransferIn' => [
                'education_grade_id' => [
                    'ruleCheckInstitutionOffersGrade' => 'This institution does not offer this Education Grade.',
                    'checkProgrammeEndDate' => 'The institution only offers the selected education grade until %s'
                ],
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.',
                    'ruleCompareDateReverse' => 'Start Date should not be earlier than Requested Date.',
                    'ruleCheckProgrammeEndDateAgainstStudentStartDate' => 'This institution does not offer the selected Education Grade anymore.',
                    'dateAlreadyTaken' => 'Start Date already taken'
                ],
                'institution_id' => [
                    'compareStudentGenderWithInstitution' => 'The selected institution only accepts %s student.'
                ],
                'institution_class_id' => [
                    'ruleClassMaxLimit' => 'Reached the maximum number of students allowed in a class.'
                ],
                'student_id' => [
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                ]
            ],
            'StudentTransferOut' => [
                'requested_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'institution_id' => [
                    'compareStudentGenderWithInstitution' => 'The selected institution only accepts %s students.'
                ],
                'student_id' => [
                    'ruleNoNewWithdrawRequestInGradeAndInstitution' => 'There is a pending withdraw application for this student.',
                    'ruleStudentNotCompletedGrade' => 'Student has already completed the selected grade.',
                ]
            ],
            'InstitutionCommittees' => [
                'meeting_date' => [
                    'ruleInAcademicPeriod' => 'Date is not within the academic period.'
                ],
                'end_time' => [
                    'ruleCompareTimeReverse' => 'End Time should not be earlier than Start Time'
                ]
            ],
            'StaffReleaseIn' => [
                'new_start_date' => [
                    'ruleCompareDateReverse' => 'Start Date should not be earlier than Current Institution End Date'
                ],
                'new_end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'StaffRelease' => [
                'previous_end_date' => [
                    'ruleCompareDateReverse' => 'Position End Date should not be earlier than Position Start Date'
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
                    'ruleContactValuePattern' => 'Please enter value with a valid format',
                    'ruleUniqueContactValue' => 'Contact value must be unique for each type',
                ],
                'preferred' => [
                    'ruleValidatePreferred' => 'There must be one Preferred Contact for each Contact Type'
                ],
            ],
            'Identities' => [
                'identity_type_id' => [
                    'ruleNotBlank' => 'Please select a Type',
                    'custom_validation' => 'Identity Type exists for this Nationality'
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
            'UserNationalities' => [
                'preferred' => [
                    'ruleValidatePreferredNationality' => 'There must be at least one Preferred Nationality'
                ]
            ],
            'UserBodyMasses' => [
                'date' => [
                    'ruleUnique' => 'Repeated Date',
                ],
                'height' => [
                    'validateDecimal' => 'Cannot be more than two decimal place',
                    'notZero' => 'Height must be more than 0',
                    'validHeight' => 'Height must be within 0 and 300 centimetre',
                ],
                'weight' => [
                    'validateDecimal' => 'Cannot be more than two decimal place',
                    'notZero' => 'Weight must be more than 0',
                    'validWeight' => 'Weight must be within 0 and 500 kilogram',
                ],
            ],
            'UserEmployments' => [
                'date_to' => [
                    'ruleCompareDateReverse' => 'Date To should not be earlier than Date From'
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
                    'ruleStudentGuardianId' => 'This guardian has already been added.'
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
            'StudentVisitRequests' => [
                'date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period'
                ]
            ],
            'StudentVisits' => [
                'date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period'
                ]
            ]
        ],
        'Profile' => [
            'Guardians' => [
                'guardian_id' => [
                    'ruleStudentGuardianId' => 'This guardian has already been added.'
                ]
            ],
            'Accounts' => [
                'current_password' => [
                    'ruleChangePassword' => 'The current password was not matched.'
                ]
            ],
        ],
        'Staff' => [
            'transferExists' => 'There is an existing transfer request for that staff.',
            'date_of_birth' => 'Date Of Birth',
            'photo_content' => 'Profile Image',
            'Leave' => [
                'date_to' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'date_from' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
            ],
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
            'EmploymentStatuses' => [
                'status_type_id' => [
                    'ruleNotBlank' => 'Please select a Type'
                ],
                'status_date' => [
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
                'deleted_competencies' => 'This competency has been removed',
                'circular_dependency' => 'This will lead to a circular dependency',
                'isNotEditable' => 'Edit operation is not allowed as there are other information linked to this record.'
            ],
            'Competencies' => [
                'min' => [
                    'ruleRange' => 'Value must be within 0 to 100'
                ],
                'max' => [
                    'ruleCompare' => 'Max value must be greater than min value',
                    'ruleRange' => 'Value must be within 0 to 100'
                ]
            ],
            'StaffTrainings' => [
                'credit_hours' => [
                    'ruleRange' => 'Value must be within 1 to 99'
                ],
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
        'Education' => [
            'EducationCycles' => [
                'admission_age' => [
                    'ruleRange' => 'Admision age must be within 0 to 99'
                ]
            ],
            'EducationGradesSubjects' => [
                'hours_required' => [
                    'ruleIsDecimal' => 'Please enter a valid Decimal value',
                    'ruleRange' => 'Value must be positive with maximum 2 decimal points and less than 1000'
                ]
            ],
            'EducationSubjects' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'EducationStages' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'EducationProgrammes' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ]
            ],
            'EducationGrades' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
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
                ],
                'special_education_needs' => 'This course is Special Educational Needs(SENs) compliant.',
            ],
            'TrainingSessions' => [
                'code' => [
                    'ruleUnique' => 'This code already exists in the system'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date'
                ]
            ],
            'TrainingSessionTraineeResults' => [
                'result' => [
                    'ruleMaxLength' => 'Result entered exceeds 10 characters'
                ],
            ],
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
        'CustomField' => [
            'text' => [
                'minLength' => 'Text should be at least %d characters',
                'maxLength' => 'Text should not exceed %d characters',
                'range' => 'Text should be between %d and %d characters'
            ],
            'number' => [
                'minValue' => 'Number should not be lesser than %d',
                'maxValue' => 'Number should not be greater than %d',
                'range' => 'Number should be between %d and %d'
            ],
            'decimal' => [
                'length' => 'Length should not exceed %d digits',
                'precision' => 'Length should not exceed %d digits or decimal places should not exceed %d digits'
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
                'academic_term' => [
                    'ruleCheckAcademicTerm' => 'Please enter an academic term for this record'
                ]
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
                ],
                'registration_start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'registration_end_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ]
            ],
            'ExaminationCentres' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code for this examination centre in the selected academic period'
                ],
                'examination_id' => [
                    'ruleNoRunningSystemProcess' => 'There is currently a running process for this examination'
                ],
            ],
            'ExaminationCentreRooms' => [
                'name' => [
                    'ruleUnique' => 'Please enter a unique name for this examination centre room'
                ],
                'size' => [
                    'ruleValidateNumeric' => 'Please enter a valid Numeric value',
                    'ruleRoomSize' => 'Room size is out of range'
                ],
                'number_of_seats' => [
                    'ruleValidateNumeric' => 'Please enter a valid Numeric value',
                    'ruleCheckRoomCapacityMoreThanStudents' => 'Number of Seats must be more than the number of students in this room',
                    'ruleSeatsNumber' => 'Number of seats is out of range'
                ]
            ],
            'ExaminationItems' => [
                'weight' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleWeightRange' => 'Value must be positive and less than 2.0'
                ],
                'code' => [
                    'ruleUniqueCodeWithinForm' => 'Code must be unique in the same examination',
                ],
                'examination_date' => [
                    'ruleCompareDateReverse' => 'Date should not be earlier than Registration End Date'
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
            'ExaminationCentresExaminationsStudents' => [
                'registration_number' => [
                    'ruleUnique' => 'Registration Number must be unique'
                ],
                'student_id' => [
                    'ruleUnique' => 'This student is already registered to the selected exam',
                    'ruleNotInvigilator' => 'This student is an invigilator in this examination'
                ]
            ],
            'ExamCentreStudents' => [
                'examination_centre_room_id' => [
                    'ruleExceedRoomCapacity' => 'There are no available seats in this room'
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
        'Risk' => [
            'TableHeader' => [
                'Criteria',
                'Operator',
                'Threshold',
                'Risk'
            ],
            'RiskCriterias' => [
                'threshold' => [
                    'ruleRange' => 'Value must be within 1 to 99',
                    'criteriaThresholdRange' => 'Value must be within %s to %s'
                ],
                'risk_value' => [
                    'ruleRange' => 'Value must be within 1 to 99'
                ]
            ]
        ],
        'Textbook' => [
            'Textbooks' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period',
                ]
            ],
        ],
        'Scholarship' => [
            'Scholarships' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period',
                ],
                'application_close_date' => [
                    'ruleCompareDateReverse' => 'Application Close Date should not be earlier than Application Open Date'
                ]
            ],
            'Applications' => [
                'requested_amount' => [
                    'ruleCheckRequestedAmount' => 'Requested amount must not exceed the Annual Award Amount',
                ]
            ],
            'ScholarshipRecipients' => [
                'approved_amount' => [
                    'comparison' => 'Approved Award Amount cannot be more than Total Award Amount',
                    'validateDecimal' => 'Value cannot be more than two decimal places',
                    'ruleCheckApprovedWithEstimated' => 'Approved Amount cannot be less than the Estimated Amounts',
                    'ruleCheckApprovedWithDisbursed' => 'Approved Amount cannot be less than the Disbursed Amounts',
                    'ruleCheckApprovedWithCollected' => 'Approved Amount cannot be less than the Collected Amounts',
                ]
            ]
        ],
        'Competency' => [
            'GradingOptions' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form'
                ],
                'point' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ]
            ],
            'CompetencyGradingTypes' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form'
                ],
            ],
            'CompetencyCriterias' => [
                'code' => [
                    'ruleUnique' => 'Please enter a unique code'
                ],
                'percentage' => [
                    'ruleIsDecimal' => 'Value is not a valid decimal',
                    'ruleRange' => 'Mark entered exceeds system limit'
                ]
            ],
            'CompetencyTemplates' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ],
            ],
            'CompetencyPeriods' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ],
            ],
        ],
        'Outcome' => [
            'OutcomeTemplates' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ]
            ],
            'OutcomePeriods' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ]
            ],
            'OutcomeGradingTypes' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ]
            ],
            'GradingOptions' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique',
                    'ruleUniqueCodeWithinForm' => 'Code must be unique from other codes in this form'
                ]
            ],
            'OutcomeCriterias' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ]
            ],
        ],
        'FieldOption' => [
            'LicenseClassifications' => [
                'name' => [
                    'ruleUnique' => 'This name already exists in the system',
                ]
            ],
            'GuardianRelations' => [
                'gender_id' => [
                    'ruleCheckGuardianGender' => 'Gender mismatch. Please check against existing records',
                ]
            ],
            'StaffPositionTitles' => [
               'position_grades' => [
                    'ruleCheckPositionGrades' => 'You are not allowed to remove the following in-use grades: %s',
                ]
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
            ],
            'ConfigAuthentication' => [
                'value' => [
                    'ruleLocalLogin' => 'You may only turn local login off if there are additional authentication method configured.'
                ]
            ],
            'ConfigSystemAuthentications' => [
                'removeActive' =>'You are not allow to remove the only active IDP record. Please turn on local login or set another IDP to be the active IDP before removing.',
                'status' => [
                    'ruleLocalLogin' => 'You may only turn local login off if there are additional authentication method configured.'
                ],
                'code' => [
                    'ruleUnique' => 'The redirect uri that has been generated before is invalid, please use the newly generated redirect uri.'
                ],
                'name' => [
                    'ruleUnique' => 'The name for the IDP has to be unique.'
                ],
            ],
            'ConfigWebhooks' => [
                'triggered_event' => [
                    'ruleNotEmpty' => 'This field cannot be left empty'
                ],
                'url' => [
                    'invalidUrl' => 'You have entered an invalid URL'
                ],
                'name' => [
                    'ruleUnique' => 'This webhook name already exists in the system',
                ],
            ],
            'ConfigStudentSettings' => [
                'max_students_per_subject' => [
                    'maxStudentLimit' => 'Numeric Value should be between %s to %s'
                ],
            ]
        ],
        'Alert' => [
            'AlertRules' => [
                'name' => [
                    'ruleUnique' => 'This field has to be unique'
                ]
            ],
        ],
        'ReportCard' => [
            'ReportCards' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period'
                ],
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
				'generate_start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'generate_end_date' => [
                    'ruleCompareDateReverse' => 'Generate End Date should not be earlier than Generate Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ]
            ],
        ],
        'ProfileTemplate' => [
            'StudentTemplates' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique for the same academic period'
                ],
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
				'generate_start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'generate_end_date' => [
                    'ruleCompareDateReverse' => 'Generate End Date should not be earlier than Generate Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ]
            ],
        ],
        'Area' => [
            'Areas' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ],
            ],
            'AreaAdministratives' => [
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ],
                'is_main_country' => [
                    'ruleValidateAreaAdministrativeMainCountry' => 'There must be at least one Main Country'
                ]
            ],
        ],
        'Survey' => [
            'SurveyForms' => [
                'custom_filters' => [
                    'ruleNotEmpty' => 'This field cannot be left empty'
                ]
            ]
        ],
        'SpecialNeeds' => [
            'SpecialNeedsReferrals' => [
                'date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ]
            ]
        ],
        'Historical' => [
            'HistoricalStaffPositions' => [
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'validDate' => 'Date should not be later than today'
                ],
                'start_date' => [
                    'validDate' => 'Date should not be later than today'
                ]
            ],
            'HistoricalStaffLeave' => [
                'date_from' => [
                    'ruleLessThanToday' => 'Date should not be later than today'
                ],
                'date_to' => [
                    'ruleCompareDateReverse' => 'Date To should not be earlier than Date From',
                    'ruleLessThanToday' => 'Date should not be later than today'
                ],
                'end_time' => [
                    'ruleCompareDateReverse' => 'End Time should not be earlier than Start Time'
                ]
            ],
            'addEdit' => 'This feature is for historical record use only. For current records, please refrain from adding record on this page.'
        ],        
        'Schedule' => [
            'ScheduleTerms' => [
                'start_date' => [
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.'
                ],
                'end_date' => [
                    'ruleCompareDateReverse' => 'End Date should not be earlier than Start Date',
                    'ruleInAcademicPeriod' => 'Date range is not within the academic period.',
                    'overlapDates' => 'Date range selected overlap with existing terms End date.'
                ],
                'code' => [
                    'ruleUniqueCode' => 'Code must be unique'
                ]
            ]
        ],
        'Archive' => [
            'lessSpace' => 'Please make sure there is enough space for backup.',
            'backupReminder' => 'Please remember to backup first before you proceed to transfer this data. Transfer is not possible for the current Academic Period. After the transfer is completed, the Academic Period will be updated to non-editable and non-visible.',
            'currentAcademic' => 'Please do not transfer data of current Academic Period.'
        ],
        'Connection' => [
            'testConnectionSuccess' => 'Connection has been established successfully.',
            'testConnectionFail' => 'Please configure correct Connection to Archive Database.',
            'transferConnectionFail' => 'Please configure connection to Archive Database.',
            'archiveConfigurationFail' => 'Please ensure configuration in Connection page is Online before Transferring data.'
        ],
        'UserNationalities' => [
            'ValidateNumberSuccess' => 'Identity number validate successfully.',
            'ValidateNumberFail' => 'Please enter correct identity number.',
            'IdentityNumberNotExist' => 'Identity number should not be blank.',
        ]

    ];


    public function getMessage($code, $options = [])
    {
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
                    if (!$defaultMessage) {
                        return false;
                    }
                    $message = '[Message Not Found]';
                    Log::write('error', 'MessagesTrait Message Not Found: '. $code);
                    break;
                }
            }
        }

        return !is_array($message) ? vsprintf(__($message), $sprintf) : $message;
    }
}
