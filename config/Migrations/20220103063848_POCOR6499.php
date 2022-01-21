<?php
use Migrations\AbstractMigration;

class POCOR6499 extends AbstractMigration
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
        // Creating backup
        $this->execute('DROP TABLE IF EXISTS `zz_6499_security_functions`');
        $this->execute('CREATE TABLE `zz_6499_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6499_security_functions` SELECT * FROM `security_functions`');

        //deleting Guardian section
        $this->execute('DELETE FROM `security_functions` WHERE `module` = "Personal" And `category` = "Guardian"');
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6499_security_functions` TO `security_functions`');
    }
}
