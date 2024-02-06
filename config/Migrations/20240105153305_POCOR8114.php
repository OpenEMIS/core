<?php
use Migrations\AbstractMigration;

class POCOR8114 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8114_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8114_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_view` = 'Institutions.index|Institutions.view' WHERE `id` = 9183 AND `category`='Manuals' AND `name` = 'Institution'");
        $this->execute("UPDATE `security_functions` SET `_edit` = 'Reports.edit' WHERE `id` = 9185 And `category`='Manuals' AND `name` = 'Reports'");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8114_security_functions` TO `security_functions`');
    }
}
