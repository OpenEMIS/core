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

        //Insert Assets import into it
        $this->insert('security_functions', [
            'name' => 'Import Institution Assets',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Assets',
            'parent_id' => 8,
            '_execute' => 'ImportInstitutionAssets.add|ImportInstitutionAssets.template|ImportInstitutionAssets.results|ImportInstitutionAssets.downloadFailed|ImportInstitutionAssets.downloadPassed',
            'order' => 151,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8032_security_functions` TO `security_functions`');
    }
}
