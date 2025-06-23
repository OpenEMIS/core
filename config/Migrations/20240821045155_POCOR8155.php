<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8155 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_8155_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8155_security_functions` SELECT * FROM `security_functions`');

        //enable Edit checkbox for Cases
        $this->execute("UPDATE `security_functions` SET `_edit` = 'Cases.edit'
        WHERE `name` = 'Cases' AND `module` = 'Personal' AND `category` = 'Cases'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8155_security_functions` TO `security_functions`');
    }

        
}
