<?php

use Phinx\Migration\AbstractMigration;

class POCOR4636 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4636_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `z_4636_institution_students_report_cards` SELECT * FROM `institution_students_report_cards`');

        $this->execute('UPDATE `institution_students_report_cards` SET `modified` = `created` WHERE `modified` IS NULL');
        $this->execute('UPDATE `institution_students_report_cards` SET `created` = `modified` WHERE DATEDIFF(`modified`, `created`) > 1');
    }

    public function down()
    {
        $this->dropTable('institution_students_report_cards');
        $this->table('z_4636_institution_students_report_cards')->rename('institution_students_report_cards');
    }
}
