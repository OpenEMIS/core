<?php

use Phinx\Migration\AbstractMigration;

class POCOR2345 extends AbstractMigration
{
    public function up()
    {
        $this->execute("ALTER TABLE `institution_classes` ADD COLUMN `secondary_staff_id` INT(11) NULL COMMENT 'links to security_users.id' AFTER `staff_id`");
    }

    public function down()
    {
        $this->execute("ALTER TABLE `institution_classes` DROP COLUMN `secondary_staff_id`");
    }
}
