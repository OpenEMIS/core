<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8767 extends AbstractMigration
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
         //START: security_functions table changes /
        $this->execute('DROP TABLE IF EXISTS `zz_8767_security_functions`');
        $this->execute('CREATE TABLE `zz_8767_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_8767_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `controller` = 'Staff' WHERE `name` = 'Leave' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff - Career'");
        /* END: security_functions table changes */
    }

   // rollback
    public function down()
    {
         //START: security_functions table changes /
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_8767_security_functions` TO `security_functions`');
        /* END: security_functions table changes */
    }
}
