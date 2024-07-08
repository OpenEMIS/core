<?php

use Phinx\Migration\AbstractMigration;

class POCOR8339 extends AbstractMigration
{
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8339_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8339_security_functions` SELECT * FROM `security_functions`');

        //enable Execute checkbox for export and import data
        $this->execute("UPDATE `security_functions` SET `_view` = 'Recipients.index|Recipients.view' 
        WHERE `name` = 'Recipients' AND `module` = 'Administration' AND `category` = 'Survey'");
        
        $this->execute("UPDATE `security_functions` SET `_view` = 'Filters.index|Filters.view' 
        WHERE `name` = 'Filters' AND `module` = 'Administration' AND `category` = 'Survey'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8339_security_functions` TO `security_functions`');
    }

}
