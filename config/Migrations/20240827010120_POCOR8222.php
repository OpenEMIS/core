<?php
use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class POCOR8222 extends AbstractMigration
{
    public function up()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        // Backup the existing table
        $this->execute('CREATE TABLE `z_8222_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `z_8222_institution_students_report_cards` SELECT * FROM `institution_students_report_cards`');

        //backup
        $this->execute('CREATE TABLE `z_8222_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8222_security_functions` SELECT * FROM `security_functions`');

        //backup
        $this->execute('CREATE TABLE `z_8222_assessments` LIKE `assessments`');
        $this->execute('INSERT INTO `z_8222_assessments` SELECT * FROM `assessments`');

        // security_functions Set Permission
        
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Gpa', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Report Cards', 'parent_id' => $parentId,'_view' => 'GpaSystem.index|GpaSystem.view', '_edit' => 'GpaSystem.edit', '_add' => 'GpaSystem.add', '_delete' => 'GpaSystem.remove', '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

       // $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 483');
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Cumulative', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Report Cards', 'parent_id' => $parentId,'_view' => 'Cumulative.index|Cumulative.view', '_edit' => 'Cumulative.edit', '_add' => 'Cumulative.add', '_delete' => 'Cumulative.remove', '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Administration' AND `category` = 'Report Cards'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Gpa Grading Type', 'controller' => 'Gpa', 'module' => 'Administration', 'category' => 'Report Cards', 'parent_id' => $parentId,'_view' => 'GpaGradingType.index|GpaGradingType.view', '_edit' => 'GpaGradingType.edit', '_add' => 'GpaGradingType.add', '_delete' => 'GpaGradingType.remove', '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
       

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;

        $record = [
            [
                'name' => 'Institution Student GPA', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parentId,'_view' => 'ReportCardGpa.index|ReportCardGpa.view', '_edit' => 'ReportCardGpa.edit', '_add' => 'ReportCardGpa.add', '_delete' => 'ReportCardGpa.remove', '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

       
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Institution Student Cumulative GPA', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Report Cards', 'parent_id' => $parentId,'_view' => 'ReportCardCumulativeGpa.index|ReportCardCumulativeGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Students - Academic'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Students - Academic'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'GPA', 'controller' => 'Students', 'module' => 'Institutions', 'category' => 'Students - Academic', 'parent_id' => $parentId,'_view' => 'StudentGpa.index|StudentGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        
        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Students - Academic'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Personal' AND `category` = 'Students - Academic'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'GPA', 'controller' => 'Profiles', 'module' => 'Personal', 'category' => 'Students - Academic', 'parent_id' => $parentId,'_view' => 'StudentGpa.index|StudentGpa.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        // Create new tables
        $this->execute("CREATE TABLE `education_grades_gpa`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `academic_period_id` int(11) NOT NULL,
                      `start_date` datetime DEFAULT NULL,
                      `end_date` datetime DEFAULT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `gpa_grading_type_id` int(11) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->execute("CREATE TABLE `institution_students_gpa`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `student_id` int(11) NOT NULL,
                      `institution_id` int(11) NOT NULL,
                      `academic_period_id` int(11) NOT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `gpa` decimal(10,2) NOT NULL,
                      `cumulative_gpa` decimal(10,2) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->execute("CREATE TABLE `gpa_grading_options`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `code` varchar(255) DEFAULT NULL,
                      `name` varchar(255) NOT NULL,
                      `description` text DEFAULT NULL,
                      `min` decimal(10,2) NOT NULL,
                      `max` decimal(10,2) NOT NULL,
                      `point` decimal(10,2) DEFAULT NULL,
                      `order` int(11) NOT NULL,
                      `visible` int(11) NOT NULL DEFAULT 1,
                      `gpa_grading_type_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $this->execute("CREATE TABLE `education_grades_cumulative_gpa`
                      (`id` char(36) NOT NULL,
                      `education_grade_gpa_id` int(11) NOT NULL,
                      `education_grade_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Bulk insert data from InstitutionStudentsReportCards to InstitutionStudentsGpa
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $recordValue = $StudentsReportCards->find()->select(['student_id', 'academic_period_id', 'institution_id', 'education_grade_id', 'gpa'])->toArray();

        $StudentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
        $dataToSave = [];
        $i = 0 ;
        foreach ($recordValue as $value) {
            if($value['gpa'] != NULL) {
                $dataToSave[] = $StudentsGpa->newEntity([
                    'student_id' => $value['student_id'],
                    'academic_period_id' => $value['academic_period_id'],
                    'institution_id' => $value['institution_id'],
                    'education_grade_id' => $value['education_grade_id'],
                    'gpa' => $value['gpa'],
                ]);
                
                $i++;

                if ($i % 500 == 0) { 
                    sleep(5);
                }
            }
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
        $this->execute('ALTER TABLE `institution_students_report_cards` DROP COLUMN `gpa`');

        // Drop the assessment_grading_type_id column
        $this->execute('ALTER TABLE `assessments` DROP COLUMN `assessment_grading_type_id`');

    }

    public function down()
    {
        // Rollback changes
        $this->execute('DROP TABLE IF EXISTS `institution_students_report_cards`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `assessments`');
        $this->execute('RENAME TABLE `z_8222_institution_students_report_cards` TO `institution_students_report_cards`');
        $this->execute('RENAME TABLE `z_8222_security_functions` TO `security_functions`');
        $this->execute('RENAME TABLE `z_8222_assessments` TO `assessments`');
    }
}
