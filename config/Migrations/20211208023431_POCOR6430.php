<?php
use Migrations\AbstractMigration;

class POCOR6430 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
         //START: security_functions table changes /
        $this->execute('DROP TABLE IF EXISTS `zz_6430_security_functions`');
        $this->execute('CREATE TABLE `zz_6430_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6430_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Institutions', `_view`='Committees.index|Committees.view',`_add` = 'Committees.add' , `_edit` = 'Committees.edit', `_delete` = 'Committees.remove' WHERE `name` = 'Institution Committees' AND `controller` = 'InstitutionCommittees' AND `module` = 'Institutions' AND `category` = 'Committees'");
        /* END: security_functions table changes */
    }

   // rollback
    public function down()
    {
         //START: security_functions table changes /
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6430_security_functions` TO `security_functions`');
        /* END: security_functions table changes */
    }
}
