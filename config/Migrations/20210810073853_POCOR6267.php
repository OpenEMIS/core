<?php
use Migrations\AbstractMigration;

class POCOR6267 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6267_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6267_security_functions` SELECT * FROM `security_functions`'); 

        // security_functions
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 486');
        //insert 
        $record = [
            [
            'name' => 'Students', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students', 'parent_id' => -1,'_view' => 'Guardians.index', '_edit' => 'Guardians.edit', '_add' => 'Guardians.add', '_delete' => 'Guardians.remove', '_execute' => NULL, 'order' => 487, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->insert('security_functions', $record);
        $row = $this->fetchRow("SELECT `id` FROM `security_functions` WHERE `controller` = 'Guardians' AND
                `module` = 'Guardian'");
        $parentId = $row['id'];

        $data = [
            [
              'name' => 'Overview', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'StudentUser.view', '_edit' => 'StudentUser.edit|StudentUser.pull', '_add' => 'StudentUser.add', '_delete' => 'StudentUser.remove', '_execute' => NULL, 'order' => 488, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Accounts', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Accounts.view', '_edit' => 'Accounts.edit', '_add' => NULL, '_delete' => 'Accounts.remove', '_execute' => NULL, 'order' => 489, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Demographic', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Demographic.view', '_edit' => 'Demographic.edit', '_add' => 'Demographic.add', '_delete' => 'Demographic.remove', '_execute' => NULL, 'order' => 490, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Identities', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Identities.index|Identities.view', '_edit' => 'Identities.edit', '_add' => 'Identities.add', '_delete' => 'Identities.remove', '_execute' => NULL, 'order' => 491, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Nationalities', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Nationalities.index|Nationalities.view', '_edit' => 'Nationalities.edit', '_add' => 'Nationalities.add', '_delete' => 'Nationalities.remove', '_execute' => NULL, 'order' => 492, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Contacts', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Contacts.index|Contacts.view', '_edit' => 'Contacts.edit', '_add' => 'Contacts.add', '_delete' => 'Contacts.remove', '_execute' => NULL, 'order' => 493, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Languages', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Languages.index|Languages.view', '_edit' => 'Languages.edit', '_add' => 'Languages.add', '_delete' => 'Languages.remove', '_execute' => NULL, 'order' => 494, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Attachments', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Attachments.index|Attachments.view', '_edit' => 'Attachments.edit', '_add' => 'Attachments.add', '_delete' => 'Attachments.remove', '_execute' => NULL, 'order' => 495, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'Comments', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'Comments.index|Comments.view', '_edit' => 'Comments.edit', '_add' => 'Comments.add', '_delete' => 'Comments.remove', '_execute' => NULL, 'order' => 496, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
              'name' => 'History', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - General', 'parent_id' => $parentId, '_view' => 'History.index|History.view', '_edit' => 'History.edit', '_add' => 'History.add', '_delete' => 'History.remove', '_execute' => NULL, 'order' => 497, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
              'name' => 'Programmes', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentProgrammes.index|StudentProgrammes.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 498, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Classes', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentClasses.index|StudentClasses.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 499, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Subjects', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentSubjects.index|StudentSubjects.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 500, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Absences', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentAbsences.index|StudentAbsences.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 501, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Behaviours', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentBehaviours.index|StudentBehaviours.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 502, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Outcomes', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentOutcomes.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 503, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Competencies', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentCompetencies.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 504, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Assessments', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentResults.index.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 505, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Examinations', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentExaminationResults.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 506, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Report Cards', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentReportCards.index|StudentReportCards.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 507, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Awards', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentProgrammes.index|StudentProgrammes.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 508, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Extracurriculars', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentProgrammes.index|StudentProgrammes.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 509, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Textbooks', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentProgrammes.index|StudentProgrammes.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 510, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Risks', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentRisks.index|StudentRisks.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 511, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Associations', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentAssociations.index|StudentAssociations.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 512, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
             'name' => 'Counselling', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'Counselling.index|Counselling.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 513, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Timetables', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Timetables', 'parent_id' => $parentId, '_view' => 'StudentScheduleTimetable.index|StudentScheduleTimetable.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 514, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Bank Accounts', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Finance', 'parent_id' => $parentId, '_view' => 'StudentBankAccounts.index|StudentBankAccounts.view', '_edit' => 'StudentBankAccounts.edit', '_add' => 'StudentBankAccounts.add', '_delete' => 'StudentBankAccounts.remove', '_execute' => NULL, 'order' => 515, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Fees', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Finance', 'parent_id' => $parentId, '_view' => 'Fees.index|Fees.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 516, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Meals', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Meal', 'parent_id' => $parentId, '_view' => 'Meals.index|Meals.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 517, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Visit Requests', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Visits', 'parent_id' => $parentId, '_view' => 'StudentVisitRequests.index|StudentVisitRequests.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 518, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Visits', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Visits', 'parent_id' => $parentId, '_view' => 'StudentVisits.index|StudentVisits.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 519, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Overview', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'Healths.index|Healths.view', '_edit' => 'Healths.edit', '_add' => 'Healths.add', '_delete' => 'Healths.remove', '_execute' => NULL, 'order' => 520, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Allergies', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthAllergies.index|HealthAllergies.view', '_edit' => 'HealthAllergies.edit', '_add' => 'HealthAllergies.add', '_delete' => 'HealthAllergies.remove', '_execute' => NULL, 'order' => 521, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Consultations', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthConsultations.index|HealthConsultations.view', '_edit' => 'HealthConsultations.edit', '_add' => 'HealthConsultations.add', '_delete' => 'HealthConsultations.remove', '_execute' => NULL, 'order' => 522, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Families', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthFamilies.index|HealthFamilies.view', '_edit' => 'HealthFamiliesHealthFamilies.edit', '_add' => 'HealthFamilies.add', '_delete' => 'HealthFamilies.remove', '_execute' => NULL, 'order' => 523, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Histories', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthHistories.index|HealthHistories.view', '_edit' => 'HealthHistories.edit', '_add' => 'HealthHistories.add', '_delete' => 'HealthHistories.remove', '_execute' => NULL, 'order' => 524, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Vaccinations', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthImmunizations.index|HealthImmunizations.view', '_edit' => 'HealthImmunizations.edit', '_add' => 'HealthImmunizations.add', '_delete' => 'HealthImmunizations.remove', '_execute' => NULL, 'order' => 525, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Medications', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthMedications.index|HealthMedications.view', '_edit' => 'HealthMedications.edit', '_add' => 'HealthMedications.add', '_delete' => 'HealthMedications.remove', '_execute' => NULL, 'order' => 526, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Tests', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'HealthTests.index|HealthTests.view', '_edit' => 'HealthTests.edit', '_add' => 'HealthTests.add', '_delete' => 'HealthTests.remove', '_execute' => NULL, 'order' => 527, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Body Mass', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'index|view', '_edit' => 'edit', '_add' => 'add', '_delete' => 'remove', '_execute' => NULL, 'order' => 528, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Insurance', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Health', 'parent_id' => $parentId, '_view' => 'index|view', '_edit' => 'edit', '_add' => 'add', '_delete' => 'remove', '_execute' => NULL, 'order' => 529, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Referrals', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Special Needs', 'parent_id' => $parentId, '_view' => 'SpecialNeedsReferrals.index|SpecialNeedsReferrals.view', '_edit' => 'SpecialNeedsReferrals.edit', '_add' => 'SpecialNeedsReferrals.add', '_delete' => 'SpecialNeedsReferrals.remove', '_execute' => 'SpecialNeedsReferrals.execute', 'order' => 530, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Assessments', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Special Needs', 'parent_id' => $parentId, '_view' => 'SpecialNeedsAssessments.index|SpecialNeedsAssessments.view', '_edit' => 'SpecialNeedsAssessments.edit', '_add' => 'SpecialNeedsAssessments.add', '_delete' => 'SpecialNeedsAssessments.remove', '_execute' => 'SpecialNeedsAssessments.execute', 'order' => 531, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Services', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Special Needs', 'parent_id' => $parentId, '_view' => 'SpecialNeedsServices.index|SpecialNeedsServices.view', '_edit' => 'SpecialNeedsServices.edit', '_add' => 'SpecialNeedsServices.add', '_delete' => 'SpecialNeedsServices.remove', '_execute' => 'SpecialNeedsServices.execute', 'order' => 532, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Devices', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Special Needs', 'parent_id' => $parentId, '_view' => 'SpecialNeedsDevices.index|SpecialNeedsDevices.view', '_edit' => 'SpecialNeedsDevices.edit', '_add' => 'SpecialNeedsDevices.add', '_delete' => 'SpecialNeedsDevices.remove', '_execute' => 'SpecialNeedsReferrals.execute', 'order' => 533, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Plans', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Special Needs', 'parent_id' => $parentId, '_view' => 'SpecialNeedsPlans.index|SpecialNeedsPlans.view', '_edit' => 'SpecialNeedsPlans.edit', '_add' => 'SpecialNeedsPlans.add', '_delete' => 'SpecialNeedsPlans.remove', '_execute' => 'SpecialNeedsPlans.execute', 'order' => 534, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Profiles', 'controller' => 'Guardians', 'module' => 'Guardian', 'category' => 'Students - Profiles', 'parent_id' => $parentId, '_view' => 'Profiles.index|Profiles.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'Profiles.execute', 'order' => 535, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

         $this->insert('security_functions', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6267_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 486');   
    }
}
