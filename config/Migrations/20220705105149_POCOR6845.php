<?php
use Migrations\AbstractMigration;

class POCOR6845 extends AbstractMigration
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
        /** backup */
        $this->execute('CREATE TABLE `zz_6845_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6845_security_functions` SELECT * FROM `security_functions`');
        /** updating existing record */
        $this->execute("UPDATE security_functions SET `_execute` = 'StudentReportCards.download' WHERE `module`='Guardian' AND `controller`='GuardianNavs' AND `name` = 'Report Cards'");
    }

    /** rollback */ 
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6845_security_functions` TO `security_functions`');
    }
}
