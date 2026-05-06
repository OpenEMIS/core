<?php

use Phinx\Migration\AbstractMigration;

class POCOR9651 extends AbstractMigration
{
    public function up()
    {
       //backup of security_functions table
        $this->execute('CREATE TABLE `zz_9651_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9651_security_functions` SELECT * FROM `security_functions`');

        // update for permission 
        $this->execute("UPDATE security_functions SET _view = 'Recipients.index|Recipients.view', _edit = '', _add = '', _delete = 'Recipients.remove' WHERE name = 'Recipients' AND controller = 'Surveys' AND module = 'Administration' AND category = 'Survey'");
    }


    //rollback migration script
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9651_security_functions` TO `security_functions`');
    }
}
