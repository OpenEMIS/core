<?php
use Migrations\AbstractMigration;

class POCOR6865 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // locale_content
        $this->execute('CREATE TABLE `z_6865_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6865_locale_contents` SELECT * FROM `locale_contents`');

        $today = date('Y-m-d H:i:s');
        $localeData = [
            [
                'en' => 'By clicking save, a transfer workflow will be initiated for this student',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Currently Allocated To',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No Class Assignment',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Import Institutions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student transfer request is added successfully',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Staff Profiles',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select Profile',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student Profiles',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Next Class',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Next',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Term',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Start Scheduling',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Students without class',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Next Class',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select All- 2 Unassigned Students',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Search Unassigned Students',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select All- 0 Unassigned Students',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Assign',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Unassign',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Search Assigned Students',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No student record found',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Attendance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Account Type',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Behaviour',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Competency Criteria',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No Competency item or Student selected',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Total Risk',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Risk Criterias',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Criteria',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Operator',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Threshold',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Risk',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Name',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Status',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Overall Average',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Comments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Amount',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Effective date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Date should be within Academic Period.',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Create',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Contract Amount',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Male(Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Male(Non-Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Female(Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Female(Non-Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Mixed(Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Mixed(Non-Functional)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Distribution',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select Programmes Meal',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Please Select week',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Change Type',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Update Details',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Summary',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Horraires',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Guidance Utilized',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Session Start Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Session End Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Meal will be automatically saved',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'No. of Students received Meal',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Meal Received',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Filter Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Position Filter',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Teaching Filter',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Non-Teaching',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Report Start Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Report End Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Workflow Status',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Wash Type',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'From Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'To Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student Attendance Summary',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student Withdrawal Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Summary Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Class Attendance Marked',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Wash Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Need Facilities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Class Attendance Marked Summary Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure Needs',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Income Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Expenditure Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Meeting Section',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Assessment Missing Mark Entry',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Assessment Period',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Recipient Payment Structures',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Recipient Academic Standings',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Schlorship Recipients',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Schlorship Disbursements(Overview)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Schlorship Disbursements(Detailed)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Schlorship Enrollments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Schlorship Disbursements(Overview)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Select Date To',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Workflow',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Workflow Records',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Workflow',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Asset Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Asset Conditions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Insurance Providers',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Insurance Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholarship Funding Sources',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Attachment Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Payment Frequencies',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Payment Frequencies',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Recipient Activity Statuses',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Disbursement Categories',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Semesters',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Institution Choices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Scholorship Institution Choices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Assessments Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Difficulties',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Referrer Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Service Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Device Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Special Needs Device Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Vaccination Type',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Overview',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Within 0 to 300 centimetres',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Land',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Building',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Floor',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Room',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Current Week 29(July 27,2022)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Sunday, 17 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Sunday, 17 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Monday, 18 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Monday, 19 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Monday, 20 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Monday, 21 July 2022',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Securities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'API Securities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institutions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Users',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Classes',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Student Admission',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Gender',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Types',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Providers',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Sectors',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Ownerships',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution Localities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Institution AreaAdministratives',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'User Nationalities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Deny',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Allow',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Appraisal Period',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Security Group',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure Need',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure Project',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure WASH Water',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure WASH Hygienes',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure WASH Sanitation',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure WASH Waste',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure WASH Sawage',
                'created_user_id' => 1,
                'created' => $today
            ],
            [
                'en' => 'Infrastructure Utility Electricity',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Infrastructure Utility Internet',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Infrastructure Utility Telephone',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Students',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Attendance Archive',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Assessment Archive',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Competency Results',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Sun',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Mon',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Tue',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Wed',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Thu',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Student Guardians',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Extracurriculars',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Status Updates',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Student Assessment',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Transport',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Academic',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Transport',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Behaviour Attachments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Behaviour',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Transition',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Professional',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Staff Qualifications',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Health',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Insurance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Body Mass',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Academic',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Position Filter',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Teaching Filter',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Non Teaching',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Mark Own Attendance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Mark Other Attendance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Release',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Appointment',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Finance',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Report Start Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Report End Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Report End Date',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Report Cards',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'All Comments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Email/Email All',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Cases',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Committees',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Institution Committees',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Institution Committee Attachments',
                'created_user_id' => 1,
                'created' => $today
            ],
            
            [

                'en' => 'General',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Leave Type',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Workflow Status',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Identities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Contacts',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Nationalities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Languages',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Comments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardian Attachments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Special Needs',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Photo',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Employment Statuses',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Positions Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Duties Report',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Extracurricular',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Detailed Staff Data',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Behaviour Attachments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Atendances Activities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Behaviour',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Training',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Schedules',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Guardians',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Carrer',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Historical Positions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Recipient Payment Structures',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Recipient Academic Standings',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Schlorship Recipients',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Schlorship Disbursements(Overview)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Schlorship Disbursement(Detailed)',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Schlorship Enrollments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Profiles',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Generate Staff Profile',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Download Staff Profile',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Generate Students Profile',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Localization',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'All Areas Level',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Labels',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Securities',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Textbooks',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Learning Outcomes',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Outcome setup',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Appraisal',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Attendances',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Logins',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Sort By',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Default Order',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Last Login',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Descending Order',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Ascending Order',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Audits',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Field Options',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Custom Fields',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'System Configurations',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Notices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Notices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Workflow Records',
                'createStaffd_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Workflow Records',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Leave',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Survey',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Forms',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Administration',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Courses',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Sessions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Results',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Needs',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Positions',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Change in Assignment',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Visits',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Requests',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Applications',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Professional Developement',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Licenses',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Cases',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Staff Transfer',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Receiving',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Sending',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Withdraw',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Addmission',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Appraisals',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Scholorships',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Calendar',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Competencies',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'GradingTypes',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Import Competency Templates',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Email Templates ',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Report Cards',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Updates',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Risks',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Default Period Name',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Assigned Name',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Users Directory',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Institution Choices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Application Attachments',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Scholarships',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Recipients',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Institutions Choices',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Payment Structures',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Disbursements',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Collections',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Academic Standings',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Meals',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Meals Programme',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffAwards',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffQualifications',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffExtracurriculars',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffMemberships',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffLicenses',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffQualifications',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'StaffQualifications',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Timetables',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => '--Select Student Template--',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => '--Select Area Level--',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => '--All Institutions--',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => '--Select Grade--',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Days within 1 to 30',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Land',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'building',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'floor',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Room',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Add New Guardian',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Add New Other',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Automated Student Withdrawal',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Column for directory list page',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Column for staff list page',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Column for student list page',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Delete Requests',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Student Settings',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'User Competencies',
                'created_user_id' => 1,
                'created' => $today
            ],
            [

                'en' => 'Where is My School Config',
                'created_user_id' => 1,
                'created' => $today
            ]
        ];

        $this->insert('locale_contents', $localeData);
        // locale_content - END
    }

    public function down()
    {
       // locale_content
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6865_locale_contents` TO `locale_contents`');
    }
}
