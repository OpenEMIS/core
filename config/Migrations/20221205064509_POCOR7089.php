<?php
use Migrations\AbstractMigration;

class POCOR7089 extends AbstractMigration
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
        // Creating backup
        $this->execute('CREATE TABLE `zz_7089_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7089_security_functions` SELECT * FROM `security_functions`');

         $this->execute("UPDATE `security_functions` SET `_execute` = 'StudentReportCard.download' WHERE `name` = 'Report Cards (Excel)' AND `controller` = 'GuardianNavs' AND `module` = 'Guardian' AND `category` = 'Students - Academic'");
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7089_security_functions` TO `security_functions`');
    }
}
