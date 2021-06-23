<?php
use Migrations\AbstractMigration;

class POCOR6194 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `z_6194_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_6194_security_roles` SELECT * FROM `security_roles`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_roles SET code = 'Superrole' WHERE name = 'Superrole'");
        $this->execute("UPDATE security_roles SET code = 'Guardian' WHERE name = 'Guardian'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `z_6194_security_roles` TO `security_roles`');
    }
}
