<?php
use Migrations\AbstractMigration;

class POCOR6655 extends AbstractMigration
{
    /**
     * Change Method.
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup of security_functions table
        $this->execute('CREATE TABLE `zz_6655_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6655_security_functions` SELECT * FROM `security_functions`');

        //updating migration
        // Institution module
        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll|StudentProfiles.index|StudentProfiles.view' WHERE name = 'Download Students Profile' AND controller = 'Institutions' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.index|StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll' WHERE name = 'Generate Students Profile' AND controller = 'Institutions' AND category = 'Profiles'");
        // Directory module
        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll|StudentProfiles.index|StudentProfiles.view' WHERE name = 'Download Students Profile' AND controller = 'Directories' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.index|StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll' WHERE name = 'Generate Students Profile' AND controller = 'Directories' AND category = 'Profiles'");
        // Personal module
        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll|StudentProfiles.index|StudentProfiles.view' WHERE name = 'Download Students Profile' AND controller = 'Profiles' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.index|StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll' WHERE name = 'Generate Students Profile' AND controller = 'Profiles' AND category = 'Profiles'");
    }

    //rollback migration script
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6655_security_functions` TO `security_functions`');
    }
}
