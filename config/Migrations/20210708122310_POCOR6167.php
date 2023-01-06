<?php
use Migrations\AbstractMigration;

class POCOR6167 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `z_6131_security_functions`');
        $this->execute('CREATE TABLE `z_6167_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6167_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission
        $this->execute("UPDATE security_functions SET `controller` = 'Institutions', `_view` = 'InstitutionTransportProviders.index|InstitutionTransportProviders.view', `_edit` = 'InstitutionTransportProviders.edit', `_add` = 'InstitutionTransportProviders.add', `_delete` = 'InstitutionTransportProviders.delete', `_execute` = 'InstitutionTransportProviders.excel' WHERE `name` = 'Providers' AND controller = 'InstitutionTransportProviders' AND module = 'Institutions' AND category = 'Transport'");
    }

 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6167_security_functions` TO `security_functions`');
    }
}
