<?php
use Migrations\AbstractMigration;

class POCOR6208 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_6208_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6208_security_functions` SELECT * FROM `security_functions`');

        //update delete function for all reports module
        $this->execute("UPDATE security_roles SET code = 'Superrole' WHERE name = 'Superrole'");
        $this->execute("UPDATE security_roles SET code = 'Guardian' WHERE name = 'Guardian'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_6208_security_functions` TO `security_functions`');
    }
}
