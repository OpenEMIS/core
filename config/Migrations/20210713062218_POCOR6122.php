<?php

use Phinx\Migration\AbstractMigration;

class POCOR6122 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        //backup
        $this->execute('CREATE TABLE `z_6122_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6122_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE security_functions SET controller = 'Institutions' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");
        $this->execute("UPDATE security_functions SET _view = 'InstitutionCalendars.index|InstitutionCalendars.view' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");
        $this->execute("UPDATE security_functions SET _edit = 'InstitutionCalendars.edit' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");
        $this->execute("UPDATE security_functions SET _add = 'InstitutionCalendars.add' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");
        $this->execute("UPDATE security_functions SET _delete = 'InstitutionCalendars.delete' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'InstitutionCalendars.excel' WHERE name = 'Calendar' AND module = 'Institutions' AND category = 'General' ");
    }
        //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6122_security_functions` TO `security_functions`');
    }

}
