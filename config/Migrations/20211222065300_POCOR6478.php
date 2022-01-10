<?php
use Migrations\AbstractMigration;

class POCOR6478 extends AbstractMigration
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
        // Backup
        $this->execute('CREATE TABLE `zz_6478_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6478_security_functions` SELECT * FROM `security_functions`');

        //update
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentAwards.index|StudentAwards.view' WHERE `name` = 'Awards' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Students - Academic'");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentExtracurriculars.index|StudentExtracurriculars.view' WHERE `name` = 'Extracurriculars' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Students - Academic'");
        $this->execute("UPDATE `security_functions` SET `_view` = 'StudentTextbooks.index|StudentTextbooks.view' WHERE `name` = 'Textbooks' AND `controller` = 'Profiles' AND `module` = 'Personal' AND `category` = 'Students - Academic'");
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6478_security_functions` TO `security_functions`');
    }
}
