<?php
use Migrations\AbstractMigration;

class POCOR6157 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6157_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6157_security_functions` SELECT * FROM `security_functions`');

        //enable execute checkbox in Map permission

        $this->execute("UPDATE security_functions SET _execute = 'Exams.excel' WHERE name = 'Exams' AND controller = 'Institutions' AND module = 'Institutions' AND category = 'Examinations'");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6157_security_functions` TO `security_functions`');
    }
}
