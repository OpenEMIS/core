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
            'name' => 'Guardian', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students', 'parent_id' => -1, '_view' => 'GuardianNavs.index|GuardianNavs.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 487, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('security_functions', $record);
        $row = $this->fetchRow("SELECT `id` FROM `security_functions` WHERE `controller` = 'GuardianNavs' AND
                `module` = 'Guardian'");
        $parentId = $row['id'];

        $data = [
            [
            'name' => 'Overview', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'General', 'parent_id' => $parentId, '_view' => 'StudentUser.index|StudentUser.view', '_edit' => 'StudentUser.edit', '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 488, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s')
            ],
            [
              'name' => 'Programmes', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentProgrammes.index|StudentProgrammes.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 489, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Classes', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentClasses.index|StudentClasses.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 490, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Subjects', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentSubjects.index|StudentSubjects.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 491, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Absences', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentAbsences.index|StudentAbsences.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 492, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Behaviours', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentBehaviours.index|StudentBehaviours.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 493, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Outcomes', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentOutcomes.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 494, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Competencies', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentCompetencies.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 495, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Assessments', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentResults.index.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 496, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Examinations', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentExaminationResults.index', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 497, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Report Cards', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentReportCards.index|StudentReportCards.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 498, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Awards', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentAwards.index|StudentAwards.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 499, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Extracurriculars', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentExtracurriculars.index|StudentExtracurriculars.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 500, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Textbooks', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentTextbooks.index|StudentTextbooks.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 501, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Risks', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentRisks.index|StudentRisks.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 501, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],[
             'name' => 'Associations', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'StudentAssociations.index|StudentAssociations.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 502, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ],
            [
             'name' => 'Counselling', 'controller' => 'GuardianNavs', 'module' => 'Guardian', 'category' => 'Students - Academic', 'parent_id' => $parentId, '_view' => 'Counselling.index|Counselling.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 503, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

         $this->insert('security_functions', $data);
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6267_security_functions` TO `security_functions`');   
    }
}
