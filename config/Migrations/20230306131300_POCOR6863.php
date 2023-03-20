<?php
use Migrations\AbstractMigration;

class POCOR6863 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */

     /**POCOR-6863
      * Onwer:Rishabh
      */
    public function up()
    {

        $data = [
            [
                'id' => 'e4020d84-d028-11e7-a675-436637e1c231',
                'module' => 'InstitutionClasses',
                'field' => 'unit',
                'module_name' => 'Institutions -> Classes',
                'field_name' => 'Unit',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ], [
                'id' => 'e991a4f8-d028-11e7-a675-436637e1c353',
                'module' => 'InstitutionClasses',
                'field' => 'course',
                'module_name' => 'Institutions -> Classes',
                'field_name' => 'Course',
                'code' => NULL,
                'name' => NULL,
                'visible' => 1,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $table = $this->table('labels');
        $table->insert($data);
        $table->saveData();


        $this->execute("CREATE TABLE IF NOT EXISTS `institution_units` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) DEFAULT NULL,
            `visible` int(1) DEFAULT '1',
			`editable` int(1) DEFAULT '1',
			`default` int(1) DEFAULT '0',
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_courses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) DEFAULT NULL,
            `visible` int(1) DEFAULT '1',
			`editable` int(1) DEFAULT '1',
			`default` int(1) DEFAULT '0',
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8");

        
        //Fields for Institutions Classes Details Page 
        
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Unit', 'class_ins_unit', 'Fields for Institutions Classes Details Page', 'Unit', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:10', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Course', 'class_ins_course', 'Fields for Institutions Classes Details Page', 'Course', '1', '', '0', '1', '1', 'Dropdown', 'completeness', '2', '2021-09-02 08:31:30', '2', '2021-08-27 00:00:00')");
        


        //Columns for Institutions Classes List Page 
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Class Name', 'class_name', 'Columns for Institutions Classes List Page', 'Class Name', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:26:59', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Unit', 'class_unit', 'Columns for Institutions Classes List Page', 'Unit', '1', '', NULL, '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:10', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Course', 'class_course', 'Columns for Institutions Classes List Page', 'Course', '1', '', '0', '1', '1', 'Dropdown', 'completeness', '2', '2021-09-02 08:31:30', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Homeroom Teacher', 'class_homeroom_teacher', 'Columns for Institutions Classes List Page', 'Homeroom Teacher', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:21', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Secondary Teacher', 'class_secondary_teacher', 'Columns for Institutions Classes List Page', 'Secondary Teacher', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:31', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Multi-grade', 'class_multi_grade', 'Columns for Institutions Classes List Page', 'Multi-grade', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Capacity', 'class_capacity', 'Columns for Institutions Classes List Page', 'Capacity', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Male Students', 'class_male_student', 'Columns for Institutions Classes List Page', 'Mmale Students', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:50', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Female Students', 'class_female_student', 'Columns for Institutions Classes List Page', 'Female Students', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Total Students', 'class_total_student', 'Columns for Institutions Classes List Page', 'Total Students', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:41', '2', '2021-08-27 00:00:00')");
        $this->execute("INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, 'Subjects', 'class_subjects', 'Columns for Institutions Classes List Page', 'Subjects', '1', '', '0', '0', '1', 'Dropdown', 'completeness', '2', '2021-09-03 08:27:50', '2', '2021-08-27 00:00:00')");

        $this->execute("ALTER TABLE `institution_classes` ADD `institution_unit_id` INT(11) NULL AFTER `institution_id`, ADD `institution_course_id` INT(11) NULL AFTER `institution_unit_id`");

    }

    public function down(){
        $this->execute('DROP TABLE IF EXISTS `institution_units`');

        $this->execute('DROP TABLE IF EXISTS `institution_courses`');

        $this->execute('DROP TABLE IF EXISTS `institution_classes`');
        $this->execute('RENAME TABLE `zz_6863_institution_classes` TO `institution_classes`');

        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6863_config_items` TO `config_items`');

    }
}
