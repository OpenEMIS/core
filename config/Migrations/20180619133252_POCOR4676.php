<?php

use Phinx\Migration\AbstractMigration;

class POCOR4676 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `institution_students_report_cards` ADD `started_on` DATETIME NULL AFTER `file_content`');
        $this->execute('ALTER TABLE `institution_students_report_cards` ADD `completed_on` DATETIME NULL AFTER `started_on`');

        $this->execute('UPDATE `institution_students_report_cards` SET `started_on` = `created` WHERE `status` IN (3, 4)');
        $this->execute('UPDATE `institution_students_report_cards` SET `completed_on` = `modified` WHERE `status` IN (3, 4)');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `institution_students_report_cards` DROP `started_on`');
        $this->execute('ALTER TABLE `institution_students_report_cards` DROP `completed_on`');
    }
}
