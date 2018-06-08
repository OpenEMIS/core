<?php

use Phinx\Migration\AbstractMigration;

class POCOR4641 extends AbstractMigration
{
    public function up()
    {
    	//back up table
    	$this->execute('CREATE TABLE `z_4641_institution_classes` LIKE `institution_classes`');
        $this->execute('INSERT INTO `z_4641_institution_classes` SELECT * FROM `institution_classes`');
        
        $this->execute('ALTER TABLE `institution_classes` ADD COLUMN `capacity` int(5) NOT NULL AFTER `class_number`');
        $update = '
            UPDATE institution_classes
            SET capacity = (
                SELECT IFNULL(value,default_value)
                FROM config_items
                WHERE code = "max_students_per_class"
            )';

        $this->execute($update);
    }
    
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_classes`');
        $this->execute('RENAME TABLE `z_4641_institution_classes` TO `institution_classes`');
    }
}
