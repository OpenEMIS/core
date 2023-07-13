<?php
use Migrations\AbstractMigration;

class POCOR7466 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_7466_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7466_security_functions` SELECT * FROM `security_functions`');
        $this->execute('UPDATE `security_functions` SET `name`="Houses" WHERE `controller`="Institutions" AND `module`="Institutions" AND `category`="Academic" AND `_view`="Associations.index|Associations.view"');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7466_security_functions` TO `security_functions`');
    }
}
