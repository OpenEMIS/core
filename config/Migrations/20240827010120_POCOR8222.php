<?php
use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class POCOR8222 extends AbstractMigration
{
    public function up()
    {
        // Backup the existing table
        $this->execute('CREATE TABLE `z_8222_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `z_8222_institution_students_report_cards` SELECT * FROM `institution_students_report_cards`');
        //backup
        $this->execute('CREATE TABLE `z_8222_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8222_security_functions` SELECT * FROM `security_functions`'); 

        // security_functions Set Permission
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 482');
        $record = [
            [
                'name' => 'Gpa', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Gpa', 'parent_id' => 2000,'_view' => 'GpaSystem.index|GpaSystem.view', '_edit' => 'GpaSystem.edit', '_add' => 'GpaSystem.add', '_delete' => 'GpaSystem.remove', '_execute' => NULL, 'order' => 483, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 483');
        $record = [
            [
                'name' => 'Cumulative', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Gpa', 'parent_id' => 3000,'_view' => 'Cumulative.index|Cumulative.view', '_edit' => 'Cumulative.edit', '_add' => 'Cumulative.add', '_delete' => 'Cumulative.remove', '_execute' => NULL, 'order' => 484, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 484');
        $record = [
            [
                'name' => 'Gpa Grading Type', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Gpa', 'parent_id' => 3000,'_view' => 'GpaGradingType.index|GpaGradingType.view', '_edit' => 'GpaGradingType.edit', '_add' => 'GpaGradingType.add', '_delete' => 'GpaGradingType.remove', '_execute' => NULL, 'order' => 485, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 560');
        $record = [
            [
                'name' => 'Institution Student GPA', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Card', 'parent_id' => 4001,'_view' => 'ReportCardGpa.index|ReportCardGpa.view', '_edit' => 'ReportCardGpa.edit', '_add' => 'ReportCardGpa.add', '_delete' => 'ReportCardGpa.remove', '_execute' => NULL, 'order' => 561, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 561');
        $record = [
            [
                'name' => 'Institution Student Cumulative GPA', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Card', 'parent_id' => 4002,'_view' => 'ReportCardCumulativeGpa.index|ReportCardCumulativeGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 562, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 563');
        $record = [
            [
                'name' => 'GPA', 'controller' => 'Students', 'module' => 'Institutions', 'category' => 'Students - Academic', 'parent_id' => 4003,'_view' => 'StudentGpa.index|StudentGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 564, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 563');
        $record = [
            [
                'name' => 'GPA', 'controller' => 'Profiles', 'module' => 'Personal', 'category' => 'Students - Academic', 'parent_id' => 4003,'_view' => 'StudentGpa.index|StudentGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 564, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        // Create new tables
        $this->execute("CREATE TABLE `education_grades_gpa`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `start_date` datetime DEFAULT NULL,
                      `end_date` datetime DEFAULT NULL,
                      `academic_period_id` int(11) NOT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `gpa_education_grade_id` int(11) NOT NULL,
                      `gpa_grading_type_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        $this->execute("CREATE TABLE `institution_students_gpa`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `student_id` int(11) NOT NULL,
                      `institution_id` int(11) NOT NULL,
                      `academic_period_id` int(11) NOT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `gpa` decimal(10,2) NOT NULL,
                      `cumulative_gpa` decimal(10,2) DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        $this->execute("CREATE TABLE `gpa_grading_types`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `code` varchar(244) NOT NULL,
                      `name` varchar(244) NOT NULL,
                      `pass_mark` int(11) DEFAULT NULL,
                      `max` int(11) DEFAULT NULL,
                      `result_type` varchar(244) DEFAULT NULL,
                      `visible` enum('1','0','','') DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        $this->execute("CREATE TABLE `gpa_grading_options`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `code` varchar(255) DEFAULT NULL,
                      `name` varchar(255) NOT NULL,
                      `description` text DEFAULT NULL,
                      `min` int(11) NOT NULL,
                      `max` int(11) NOT NULL,
                      `point` decimal(10,2) DEFAULT NULL,
                      `order` int(11) NOT NULL,
                      `visible` int(11) NOT NULL DEFAULT 1,
                      `gpa_grading_type_id` int(11) NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        $this->execute("CREATE TABLE `cumulative_gpa_grade`
                      (`id` char(36) NOT NULL,
                      `education_grade_gpa_id` int(11) NOT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

        // Bulk insert data from InstitutionStudentsReportCards to InstitutionStudentsGpa
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $recordValue = $StudentsReportCards->find()->select(['student_id', 'academic_period_id', 'institution_id', 'education_grade_id', 'gpa'])->toArray();

        $StudentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $dataToSave = [];
        foreach ($recordValue as $value) {
            $dataToSave[] = $StudentsGpa->newEntity([
                'student_id' => $value['student_id'],
                'academic_period_id' => $value['academic_period_id'],
                'institution_id' => $value['institution_id'],
                'education_grade_id' => $value['education_grade_id'],
                'gpa' => $value['gpa'] ?: 0.00
            ]);
        }

        if (!empty($dataToSave)) {
            $StudentsGpa->getConnection()->transactional(function () use ($StudentsGpa, $dataToSave) {
                if ($StudentsGpa->saveMany($dataToSave)) {
                    // Success handling
                } else {
                    // Failure handling
                }
            });
        }
        // Drop the gpa column 
        $this->execute('ALTER TABLE `institution_students_report_cards` DROP COLUMN `gpa` decimal(6,2) COLLATE utf8_general_ci');

        // Drop the assessment_grading_type_id column 
        $this->execute('ALTER TABLE `assessments` DROP COLUMN `assessment_grading_type_id` int(11) COLLATE utf8_general_ci');
    }

    public function down()
    {
        // Rollback changes
        $this->execute('RENAME TABLE `z_8222_institution_students_report_cards` TO `institution_students_report_cards`');
        $this->execute('RENAME TABLE `z_8222_security_functions` TO `security_functions`');
        // Drop table if exist
        $this->execute('DROP TABLE IF EXISTS `education_grades_gpa`');
        $this->execute('RENAME TABLE `z_8222_education_grades_gpa` TO `education_grades_gpa`');
        $this->execute('DROP TABLE IF EXISTS `institution_students_gpa`');
        $this->execute('RENAME TABLE `z_8222_institution_students_gpa` TO `institution_students_gpa`');
        $this->execute('DROP TABLE IF EXISTS `gpa_grading_options`');
        $this->execute('RENAME TABLE `z_8222_gpa_grading_options` TO `gpa_grading_options`');
        $this->execute('DROP TABLE IF EXISTS `gpa_grading_types`');
        $this->execute('RENAME TABLE `z_8222_gpa_grading_types` TO `gpa_grading_types`');
    }
}
