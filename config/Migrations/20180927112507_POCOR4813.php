<?php
use Phinx\Migration\AbstractMigration;

class POCOR4813 extends AbstractMigration
{
    public function up()
    {
        // add delete permission
        $sql = 'UPDATE `security_functions`
                SET `_delete` = "Surveys.remove"
                WHERE `id` = 1025';

        $this->execute($sql);
        // end security_functions
    }

    // rollback
    public function down()
    {
        $sql = 'UPDATE `security_functions`
                SET `_delete` = null
                WHERE `id` = 1025';

        $this->execute($sql);
    }
}
