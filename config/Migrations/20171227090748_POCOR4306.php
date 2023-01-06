<?php
use Migrations\AbstractMigration;

class POCOR4306 extends AbstractMigration
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
        $table = $this->table('locale_contents');
        $this->execute("UPDATE locale_contents SET en = 'Professional' WHERE en = 'Professional Development'");
        $this->execute("UPDATE locale_contents SET en = 'File size should not be larger than %s.' WHERE en = '*File size should not be larger than 2MB.'");
        $data = [
            [
                'en' => 'Student Bank Accounts',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Calendar',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Advisable photo dimension %width by %height',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'School Holiday',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Water',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Uploaded On',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Public Holiday',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'School Open',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'School Closed',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Event',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Attachments',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Advisable logo dimension %width by %height',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Format Supported: %s',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Academic Institution',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Non-Academic Institution',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Recommended Maximum Records: %s',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Quality Rubrics',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Quality Visits',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'The record has been deleted successfully.',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Requested Date',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Transfer Out',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Change In Assignment',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Subjects',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Rubrics',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Visits',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Total Male Students',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Total Female Students',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Approve',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Priority',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Need Type',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'High',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Medium',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Low',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Date Determined',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Date Started',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Date Completed',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Projects',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Contract Date',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Funding Source',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Funding Source Description',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Contract Amount ',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Date Started',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Date Completed',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Associated Needs',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Wash',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Sanitation',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Hygiene',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Waste',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Sewage',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'There are no records.',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Functionality',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Proximity',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Quantity',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Quality',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Accessibility',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Total Female',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Total Male',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Total Mixed',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Use',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Hygiene Education',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Soap/Ash Availability',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utilities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Electricity',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Internet',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Purpose',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Bandwidth',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Transport',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Sanitation',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Buses',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Trips',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Plate Number',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Capacity',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Bus Type',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Features',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Repeat',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Status Type',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Trip Type',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Status Date',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Comment Type',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Organisation',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Training Categories',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Competency Sets',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Bank Branches',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Guardian Relations',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Position Grades',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Position Titles',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Qualification Levels',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Qualification Titles',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Qualification Specialisation',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Quality Visit Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Salary Addition Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Salary Deduction Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Achievement Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Course Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Field Studies',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Levels',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Traing Mode Deliveries',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Need Competencies',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Need Standards',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Need Sub Standards',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Priorities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Requirements',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Result Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Specialisation',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Contact Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Employment Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Extracurricular Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Identity Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'License Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'License Classifications',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Special Need Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Special Need Difficulties',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Countries',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Comment Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Behavior Classifications',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure Ownerships',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure Conditions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure Need Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure Project Funding Sources',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Functionalities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Proximities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Quantities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Qualities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Water Accessibilities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sanitation Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sanitation Uses',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sanitation Qualities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sanitation Accessibilities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Hygiene Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Hygiene Soap/Ash Availabilities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Hygiene Educations',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Waste Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Waste Functionalities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sewage Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure WASH Sewage Functionalities',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Electricity Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Electricity Conditions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Internet Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Internet Conditions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Internet Bandwidths',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Telephone Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Utility Telephone Conditions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Allergy Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Consultation Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Immunization Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Relationships',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Test Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Transport Features',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Bus Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Trip Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Land',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Floor',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Iso',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Direction',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Url',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Client ID',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Private Key',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Public Key',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'No Of Users',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Question',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Survey Form Section',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Cases',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Positions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Institution Surveys',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Position Profiles',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Staff Transfer In',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Applications',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Courses',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Sessions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Outcome Template',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Outcome Period',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Performance',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Outcomes',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Banks',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Qualification Specialisations',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Mode Deliveries',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Need Categories',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Training Specialisations',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Employment Status Types',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Behaviour Classifications',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Infrastructure Conditions',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Programme Orientations',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Themes',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Application Name',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Login Page Image',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Logo',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ],
            [
                'en' => 'Colour',
                'created_user_id' => 1,
                'created' => '2017-12-27 17:09:49'
            ]
        ];
        $table->insert($data)->save();
    }
}
