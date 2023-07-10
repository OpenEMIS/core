<?php

use Phinx\Migration\AbstractMigration;

class POCOR6172 extends AbstractMigration
{
    /**
     * @author Dr Khindol Madraimov khindol.madraimov@gmail.com
     * Create configuration item to allow users to enroll students to multiple institutions
     * name 'Multiple Institutions Student Enrollment',
     * code 'multiple_institutions_student_enrollment',
     * type 'Student Settings'
     * default value 1
     */
    public function up()
    {
        //Backup table
        $this->execute('CREATE TABLE `zz_6172_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_6172_config_items` SELECT * FROM `config_items`');
        $this->execute("INSERT IGNORE INTO `config_items` (id, 
        name, 
        code,
        type, 
        label, 
        value, 
        value_selection, 
        default_value, 
        editable, 
        visible, 
        field_type, 
        option_type, 
        modified_user_id, 
        modified, 
        created_user_id, 
        created) VALUES (NULL, 
        'Multiple Institutions Student Enrollment', 
        'multiple_institutions_student_enrollment', '
        Student Settings', 
        'Multiple Institutions Student Enrollment', 
        '1', 
        '', 
        '0',
        1, 
        1, 
        'Dropdown', 'yes_no', null, null, 1, '2023-05-31 12:55:59')");
    }

    public function down()
    {
        //Restore table
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_6172_config_items` TO `config_items`');
    }

}
