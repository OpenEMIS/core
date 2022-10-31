<?php

use Phinx\Migration\AbstractMigration;

class POCOR4107 extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE `security_functions` SET `_edit` = 'Assessments.edit|Assessments.downloadTemplate' WHERE `id` = 5010");
    }

    public function down()
    {
        $this->execute("UPDATE `security_functions` SET `_edit` = 'Assessments.edit' WHERE `id` = 5010");
    }
}
