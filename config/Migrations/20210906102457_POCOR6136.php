<?php
use Migrations\AbstractMigration;

class POCOR6136 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6136_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6136_security_functions` SELECT * FROM `security_functions`');

        //enable Execute checkbox for export data
        $this->execute("UPDATE security_functions SET _execute = 'Qualifications.excel' WHERE name = 'Qualifications' AND controller = 'Staff' AND category = 'Staff - Professional' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6136_security_functions` TO `security_functions`');
    }
}
