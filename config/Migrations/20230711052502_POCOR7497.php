<?php
use Migrations\AbstractMigration;

class POCOR7497 extends AbstractMigration
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

        $this->execute('CREATE TABLE `zz_7497_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7497_security_functions` SELECT * FROM `security_functions`');

        $this->execute('UPDATE security_functions SET `_edit`="StudentCurriculars.edit", `_add`="StudentCurriculars.add", `_delete`="StudentCurriculars.delete", `_execute`="StudentCurriculars.execute" WHERE `name`="Curriculars Students" AND `category`="Students - Academic" AND `module`="Institutions"');
    }

    public function down()
    {

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7497_security_functions` TO `security_functions`');
    }
}
