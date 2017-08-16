<?php

use Phinx\Migration\AbstractMigration;

class POCOR4078 extends AbstractMigration
{
    // commit
    public function up()
    {
        // security_functions change Quality to Rubrics
        $sql = 'UPDATE `security_functions`
                SET `name` = "Rubrics"
                WHERE `id` = 6004';

        $this->execute($sql);
        // end security_functions
    }

    // rollback
    public function down()
    {
        // security_functions change Rubrics to Quality
        $sql = 'UPDATE `security_functions`
                SET `name` = "Quality"
                WHERE `id` = 6004';

        $this->execute($sql);
        // end security_functions
    }
}
