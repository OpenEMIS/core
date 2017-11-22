<?php

use Phinx\Migration\AbstractMigration;

class V3110 extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `examination_centres` CHANGE `telephone` `telephone` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL');
        $this->execute('ALTER TABLE `examination_centres` CHANGE `fax` `fax` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL');
    }

    public function down()
    {

    }
}
