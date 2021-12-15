<?php
use Migrations\AbstractMigration;

class POCOR6416 extends AbstractMigration
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
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `zz_6416_security_functions`');
        $this->execute('CREATE TABLE `zz_6416_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6416_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'Lands.excel|Lands.excel|Lands.excel|Lands.excel' WHERE `name` = 'Infrastructure' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Details'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6416_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
