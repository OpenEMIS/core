<?php

use Phinx\Migration\AbstractMigration;

class POCOR9634 extends AbstractMigration
{

    /**
     * Update security_functions
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
       //backup of security_functions table
        $this->execute('CREATE TABLE `zz_9634_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9634_security_functions` SELECT * FROM `security_functions`');

        // update for permission 
        $this->execute("UPDATE security_functions SET _view = 'Filters.index|Filters.view', _edit = 'Filters.edit', _add = 'Filters.add', _delete = 'Filters.remove' WHERE name = 'Filters' AND controller = 'Surveys' AND module = 'Administration' AND category = 'Survey'");

        $this->execute("UPDATE security_functions SET _view = 'Recipients.index|Recipients.view', _edit = '', _add = '', _delete = '' WHERE name = 'Recipients' AND controller = 'Surveys' AND module = 'Administration' AND category = 'Survey'");
    }


    //rollback migration script
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9634_security_functions` TO `security_functions`');
    }
}
