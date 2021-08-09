<?php
use Migrations\AbstractMigration;

class POCOR5695 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public $autoId = false;
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_5695_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_5695_security_functions` SELECT * FROM `security_functions`');

        $this->execute('CREATE TABLE `z_5695_training_courses` LIKE `training_courses`');
        $this->execute('INSERT INTO `z_5695_training_courses` SELECT * FROM `training_courses`');

        $this->execute('CREATE TABLE `z_5695_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5695_locale_contents` SELECT * FROM `locale_contents`');

        $this->execute('CREATE TABLE `z_5695_training_session_trainee_results` LIKE `training_session_trainee_results`');
        $this->execute('INSERT INTO `z_5695_training_session_trainee_results` SELECT * FROM `training_session_trainee_results`');

        $this->execute('CREATE TABLE `z_5695_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_5695_labels` SELECT * FROM `labels`');

        $this->execute('CREATE TABLE `zz_5695_import_mapping` LIKE `import_mapping`');
        $this->execute('INSERT INTO `zz_5695_import_mapping` SELECT * FROM `import_mapping`');

        //enable add button in Profile > Staff > Training 
        $this->execute("UPDATE security_functions SET _add = 'TrainingNeeds.add' WHERE name = 'Training Needs' AND controller = 'Profiles' AND module = 'Personal' AND category = 'Staff - Training'");

        // sql to create training_course_categories
        $this->execute("CREATE TABLE `training_course_categories` (
                          `id` int(11) NOT NULL,
                          `name` varchar(50) NOT NULL,
                          `order` int(3) NOT NULL,
                          `visible` int(1) NOT NULL DEFAULT 1,
                          `editable` int(1) NOT NULL DEFAULT 1,
                          `default` int(1) NOT NULL DEFAULT 0,
                          `international_code` varchar(50) DEFAULT NULL,
                          `national_code` varchar(50) DEFAULT NULL,
                          `modified_user_id` int(11) DEFAULT NULL,
                          `modified` datetime DEFAULT NULL,
                          `created_user_id` int(11) NOT NULL,
                          `created` datetime NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the course category records for staff'");

        $this->execute('ALTER TABLE `training_course_categories` ADD PRIMARY KEY (`id`)');
        $this->execute('ALTER TABLE `training_course_categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT');

        // ADD COLUMN training_course_category_id in training_courses table
        $this->execute("ALTER TABLE `training_courses` ADD COLUMN `training_course_category_id` int(11) NOT NULL COMMENT 'links to training_course_categories.id' AFTER `training_course_type_id`");

        $this->execute("ALTER TABLE `training_courses` ADD INDEX( `training_course_category_id`)"); 

        //add columns attendance_days, certificate_number, practical
        $this->execute("ALTER TABLE `training_session_trainee_results`  ADD `attendance_days` VARCHAR(10) NOT NULL  AFTER `result`,  ADD `certificate_number` VARCHAR(10)  NOT NULL  AFTER `attendance_days`, ADD `practical` VARCHAR(10)  NOT NULL  AFTER `certificate_number`;");

        $this->execute("ALTER TABLE `training_session_trainee_results` CHANGE `attendance_days` `attendance_days` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `certificate_number` `certificate_number` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `practical` `practical` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        
        // labels
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
          values (uuid(), 'TrainingCourses', 'training_course_category_id', 'Administration -> Training -> Course', 'Course Category', 1, 1, NOW())");

        //locale conversion
        $now = date('Y-m-d H:i:s');
        $localeContent = [
            [
                'en' => 'Course Category',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Attendance Days',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Certificate Number',
                'created_user_id' => 1,
                'created' => $now
            ],
            [
                'en' => 'Practical',
                'created_user_id' => 1,
                'created' => $now
            ]
        ];

        $this->insert('locale_contents', $localeContent);

        //import_mapping for import training
        $data = [
            [
                'model' => 'Training.TrainingSessionTraineeResults',
                'column_name' => 'result_types',
                'description' => '',
                'order' => 67,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => '',
                'lookup_model' => 'TrainingResultTypes',
                'lookup_column' => 'name'
            ],
            [
                'model' => 'Training.TrainingSessionTraineeResults',
                'column_name' => 'training_session',
                'description' => '',
                'order' => 32,
                'is_optional' => 0,
                'foreign_key' => 3,
                'lookup_plugin' => '',
                'lookup_model' => 'TrainingSessions',
                'lookup_column' => 'code'
            ],
            [
                'model' => 'Training.TrainingSessionTraineeResults',
                'column_name' => 'OpenEMIS_ID',
                'description' => '',
                'order' => 27,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => '',
                'lookup_model' => '',
                'lookup_column' => ''
            ],
            [
                'model' => 'Training.TrainingSessionTraineeResults',
                'column_name' => 'results',
                'description' => '',
                'order' => 90,
                'is_optional' => 0,
                'foreign_key' => 0,
                'lookup_plugin' => '',
                'lookup_model' => '',
                'lookup_column' => ''
            ]
        ];

        $this->insert('import_mapping', $data);  
        //update security function for permission
        $importTrainingSql = "UPDATE security_functions
                                SET `_execute` = 'ImportTrainingSessionTraineeResults.add|ImportTrainingSessionTraineeResults.template|ImportTrainingSessionTraineeResults.results|ImportTrainingSessionTraineeResults.downloadFailed|ImportTrainingSessionTraineeResults.downloadPassed'
                                WHERE `id` = 5041 AND module ='Administration' AND category ='Trainings'";

        $this->execute($importTrainingSql);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_5695_security_functions` TO `security_functions`');

        $this->execute('DROP TABLE IF EXISTS `training_courses`');
        $this->execute('RENAME TABLE `z_5695_training_courses` TO `training_courses`');

        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_5695_labels` TO `labels`');

        $this->execute('DROP TABLE IF EXISTS `training_session_trainee_results`');
        $this->execute('RENAME TABLE `z_5695_training_session_trainee_results` TO `training_session_trainee_results`');

        $this->execute('DROP TABLE IF EXISTS `import_mapping`');
        $this->execute('RENAME TABLE `z_5695_import_mapping` TO `import_mapping`');

        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5695_locale_contents` TO `locale_contents`');
    }
}
