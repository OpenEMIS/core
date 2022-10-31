<?php
use Migrations\AbstractMigration;

class POCOR6362 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6362_security_functions`');
        $this->execute('CREATE TABLE `zz_6362_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6362_security_functions` SELECT * FROM `security_functions`');

        $this->execute("UPDATE `security_functions` SET `_execute` = 'BulkStudentTransferIn.edit' WHERE `name` = 'Student Transfer In' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Students'");

        $this->execute("UPDATE `security_functions` SET `_execute` = 'Transfer.add|BulkStudentTransferOut.edit' WHERE `name` = 'Student Transfer Out' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Students'");
        /** END: security_functions table changes */
    }

    //rollback
    public function down()
    {
        /** START: security_functions table changes */
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6362_security_functions` TO `security_functions`');
        /** END: security_functions table changes */
    }
}
