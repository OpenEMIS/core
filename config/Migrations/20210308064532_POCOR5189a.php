<?php
use Migrations\AbstractMigration;

class POCOR5189a extends AbstractMigration
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
         // Backup table for security_functions
        $this->execute('CREATE TABLE `zz_5189a_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5189a_security_functions` SELECT * FROM `security_functions`');
        $this->execute("UPDATE `security_functions` SET `controller` = 'Staff'   WHERE `name` = 'Associations' AND `category` = 'Staff - Career'");
       
    }

      // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5189a_security_functions` TO `security_functions`');
    }
}
