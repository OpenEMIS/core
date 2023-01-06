<?php

use Phinx\Migration\AbstractMigration;

class POCOR4105 extends AbstractMigration
{
    // commit
    public function up()
    {
        // institution_class_students
        $this->execute('CREATE TABLE `z_4105_institution_class_students` LIKE `institution_class_students`');
        $this->execute('INSERT INTO `z_4105_institution_class_students` SELECT * FROM `institution_class_students`');

        // to delete orphan records
        $this->execute('
            DELETE FROM `institution_class_students`
            WHERE NOT EXISTS (SELECT 1 FROM `institution_classes` WHERE `institution_classes`.`id` = `institution_class_students`.`institution_class_id`)
        ');
    }

    // rollback
    public function down()
    {
        // institution_class_students
        $this->execute('DROP TABLE IF EXISTS `institution_class_students`');
        $this->execute('RENAME TABLE `z_4105_institution_class_students` TO `institution_class_students`');
    }
}
