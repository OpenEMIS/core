<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8979 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `z_8979_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8979_security_functions` SELECT * FROM `security_functions`');

        $this->execute("INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`,`parent_id`,`_view`,`_edit`,`_add`,`_delete`,`_execute`,`order`,`visible`,`description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
        ('Transactions', 'Institutions', 'Institutions','Finance',8,'Transactions.index|Transactions.view','Transactions.edit','Transactions.add','Transactions.remove',NULL,569,1,NULL,NULL,NULL,1, NOW())");
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8979_security_functions` TO `security_functions`');
    }
}
