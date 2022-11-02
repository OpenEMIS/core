<?php

use Phinx\Migration\AbstractMigration;

class POCOR4898 extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE config_items
            SET `default_value` = 200
            WHERE `code` = 'max_students_per_class' or `code` = 'max_students_per_subject';");
    }

    public function down()
    {
        $this->execute("UPDATE config_items SET `default_value` = 100 WHERE `code` = 'max_students_per_class' or `code` = 'max_students_per_subject'");
    }

}
