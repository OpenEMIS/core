<?php
use Migrations\AbstractMigration;

class POCOR8032 extends AbstractMigration
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
        // create backup for security_functions     
        $this->execute('CREATE TABLE `z_8032_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8032_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'InstitutionAssets.excel|ImportInstitutionAssets.add|ImportInstitutionAssets.template|ImportInstitutionAssets.results|ImportInstitutionAssets.downloadFailed|ImportInstitutionAssets.downloadPassed' WHERE `category`='Details' AND `name` = 'Assets' AND controller='Institutions' AND module = 'Institutions'");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8032_security_functions` TO `security_functions`');
    }
}
