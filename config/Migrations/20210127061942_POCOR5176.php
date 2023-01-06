<?php
use Migrations\AbstractMigration;

class POCOR5176 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_5176_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_5176_security_functions` SELECT * FROM `security_functions`');
        // End
        $this->execute("UPDATE `security_functions` SET `_execute` = 'Visits.excel' WHERE `_execute` = 'Visits.download' AND `name` = 'Visits'");
        $this->execute("UPDATE `security_functions` SET `_execute` = 'VisitRequests.excel' WHERE `_execute` = 'VisitRequests.download' AND `name` = 'Visit Requests'");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_5176_security_functions` TO `security_functions`');
    }
}
