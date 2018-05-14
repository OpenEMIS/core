<?php

use Phinx\Migration\AbstractMigration;

class POCOR4387 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4387_workflow_transitions` LIKE `workflow_transitions`');
        $this->execute('INSERT INTO `z_4387_workflow_transitions` SELECT * FROM `workflow_transitions`');

    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `workflow_transitions`');
        $this->execute('RENAME TABLE `z_4387_workflow_transitions` TO `workflow_transitions`');
    }
}
