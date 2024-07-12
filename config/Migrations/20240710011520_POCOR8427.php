<?php

use Phinx\Migration\AbstractMigration;

class POCOR8427 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8427_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8427_security_functions` SELECT * FROM `security_functions`');

        //enable Execute checkbox for export and import data
        $this->execute("UPDATE `security_functions` SET `_view` = 'Filters.index|Filters.view' ,`_edit` = 'Filters.edit',`_add` = 'Filters.add',`_delete` = 'Filters.remove'
        WHERE `name` = 'Filters' AND `module` = 'Administration' AND `category` = 'Survey'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8427_security_functions` TO `security_functions`');
    }

}
