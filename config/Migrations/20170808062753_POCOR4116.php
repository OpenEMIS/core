<?php

use Phinx\Migration\AbstractMigration;

class POCOR4116 extends AbstractMigration
{
    // commit
    public function up()
    {
        // update the delete to 'delete' instead of remove
        $this->execute('UPDATE `security_functions` SET `_delete` = "delete" WHERE `id` = "5021"');
    }

    // rollback
    public function down()
    {
        $this->execute('UPDATE `security_functions` SET `_delete` = "remove" WHERE `id` = "5021"');
    }
}
