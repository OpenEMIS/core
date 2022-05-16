<?php
use Migrations\AbstractMigration;

class POCOR6667 extends AbstractMigration
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
        //backup
        $this->execute('CREATE TABLE `zz_6667_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6667_security_functions` SELECT * FROM `security_functions`');

        //update view
        $this->execute("UPDATE security_functions SET _view = 'InstitutionProfiles.view|InstitutionProfiles.index' WHERE name = 'Download Institutions Profile' AND module = 'Institutions' AND controller = 'Institutions' AND category = 'Profiles' ");


        $this->execute("UPDATE security_functions SET _view = 'StaffProfiles.view|StaffProfiles.index' WHERE name = 'Download Staff Profile' AND module = 'Institutions' AND controller = 'Institutions' AND category = 'Profiles' ");
        $this->execute("UPDATE security_functions SET _view = 'StaffProfiles.view|StaffProfiles.index' WHERE name = 'Download Staff Profile' AND module = 'Directory' AND controller = 'Directories' AND category = 'Profiles' ");
        $this->execute("UPDATE security_functions SET _view = 'StaffProfiles.view|StaffProfiles.index' WHERE name = 'Download Staff Profile' AND module = 'Personal' AND controller = 'Profiles' AND category = 'Profiles' ");

        $this->execute("UPDATE security_functions SET _view = 'StudentProfiles.view|StudentProfiles.index' WHERE name = 'Download Students Profile' AND module = 'Institutions' AND controller = 'Institutions' AND category = 'Profiles' ");
        $this->execute("UPDATE security_functions SET _view = 'StudentProfiles.view|StudentProfiles.index' WHERE name = 'Download Students Profile' AND module = 'Directory' AND controller = 'Directories' AND category = 'Profiles' ");
        $this->execute("UPDATE security_functions SET _view = 'StudentProfiles.view|StudentProfiles.index' WHERE name = 'Download Students Profile' AND module = 'Personal' AND controller = 'Profiles' AND category = 'Profiles' ");
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6667_security_functions` TO `security_functions`');
    }
}
