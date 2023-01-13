<?php

use Phinx\Migration\AbstractMigration;

class Pocor7095 extends AbstractMigration
{
    public function up()
    {
        $this->execute("ALTER TABLE `institution_subject_staff` ADD INDEX( `start_date`, `end_date`)");
        $this->execute("ALTER TABLE `institutions` ADD INDEX(`name`)");
    }

    public function down()
    {
        $this->execute("ALTER TABLE `institution_subject_staff` DROP INDEX( `start_date`, `end_date`);");
        $this->execute("ALTER TABLE `institutions` DROP INDEX(`name`)");
    }
}
