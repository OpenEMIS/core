<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9436 extends AbstractMigration
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
        $this->execute('CREATE TABLE `z_9436_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_9436_security_functions` SELECT * FROM `security_functions`');


        $sql = "UPDATE `security_functions` SET `_execute` = 'StudentWithdraw.edit|StudentWithdraw.view|StudentWithdraw.remove' WHERE `security_functions`.`name` = 'Student Withdraw' AND `security_functions`.`controller` = 'Institutions' AND `security_functions`.`module` = 'Institutions' AND `security_functions`.`category` = 'Students'";
        $this->execute($sql);
          

    }

    public function down()
    {
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_9436_security_functions` TO `security_functions`');
    }
}
