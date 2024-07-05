<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8385 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup
        $this->execute('CREATE TABLE `z_8385_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8385_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET
        `controller` = 'Institutions', `_view` = 'InfrastructureUtilityTelephones.view|InfrastructureUtilityTelephones.index|InfrastructureUtilityTelephones.download', `_edit` = 'InfrastructureUtilityTelephones.edit', `_add` = 'InfrastructureUtilityTelephones.add', `_delete` = 'InfrastructureUtilityTelephones.delete'
        WHERE `name` = 'Infrastructure Utility Telephone' AND `module` = 'Institutions' AND `category` = 'Details'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8385_security_functions` TO `security_functions`');
    }
}
