<?php
use Migrations\AbstractMigration;

class POCOR6653 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6653_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6653_security_functions` SELECT * FROM `security_functions`');

        //updating migration
        // Institutions Profiles
        $this->execute("UPDATE security_functions SET _execute = 'InstitutionProfiles.downloadExcel|InstitutionProfiles.publish|InstitutionProfiles.unpublish|InstitutionProfiles.email|InstitutionProfiles.downloadAll|InstitutionProfiles.publishAll|InstitutionProfiles.unpublishAll|InstitutionProfiles.view|InstitutionProfiles.index' WHERE name = 'Download Institutions Profile' AND controller = 'Institutions' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'InstitutionProfiles.index|InstitutionProfiles.view|InstitutionProfiles.generate|InstitutionProfiles.generateAll' WHERE name = 'Generate Institutions Profile' AND controller = 'Institutions' AND category = 'Profiles'");
        // Student Profiles
        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll|StudentProfiles.index|StudentProfiles.view' WHERE name = 'Download Students Profile' AND controller = 'Institutions' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'StudentProfiles.index|StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll' WHERE name = 'Generate Students Profile' AND controller = 'Institutions' AND category = 'Profiles'");
        // Staff Profiles
        $this->execute("UPDATE security_functions SET _execute = 'StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll|StaffProfiles.index|StaffProfiles.view' WHERE name = 'Download Staff Profile' AND controller = 'Institutions' AND category = 'Profiles'");

        $this->execute("UPDATE security_functions SET _execute = 'StaffProfiles.index|StaffProfiles.view|StaffProfiles.generate|StaffProfiles.generateAll' WHERE name = 'Generate Staff Profile' AND controller = 'Institutions' AND category = 'Profiles'");
        
    }

    //rollback migration script
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6653_security_functions` TO `security_functions`');
    }
}
