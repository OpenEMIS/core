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

        /*$this->execute("ALTER TABLE `training_session_trainee_results`  ADD `attendance_days` VARCHAR(10) NOT NULL  AFTER `result`,  ADD `certificate_number` VARCHAR(10)  NOT NULL  AFTER `attendance_days`, ADD `practical` VARCHAR(10)  NOT NULL  AFTER `certificate_number`;");*/
        
        // labels
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created) 
          values (uuid(), 'TrainingCourses', 'training_course_category_id', 'Administration -> Training -> Course', 'Course Category', 1, 1, NOW())");

        //locale conversion
        $localeContent = [
            [
                'en' => 'Course Category',
                'created_user_id' => 1,
                'created' => 'NOW()'
            ],
            [
                'en' => 'Attendance Days',
                'created_user_id' => 1,
                'created' => 'NOW()'
            ],
            [
                'en' => 'Certificate Number',
                'created_user_id' => 1,
                'created' => 'NOW()'
            ],
            [
                'en' => 'Practical',
                'created_user_id' => 1,
                'created' => 'NOW()'
            ]
        ];

        $this->insert('locale_contents', $localeContent);

        /*SELECT *  FROM `phinxlog` WHERE `migration_name` LIKE '%POCOR5695%'*/
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

        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Course Category'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Attendance Days'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Certificate Number'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Practical'");
       
    }
}
