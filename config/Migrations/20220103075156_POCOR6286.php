<?php
use Migrations\AbstractMigration;

class POCOR6286 extends AbstractMigration
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
        $this->execute('DROP TABLE IF EXISTS `zz_6286_security_functions`');
        $this->execute('CREATE TABLE `zz_6286_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6286_security_functions` SELECT * FROM `security_functions`');

        //getting parent_id value
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'General'");
        $parentId = $row['id'];

        //getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");

        //inserting record
        $data = [
            [   
                'name' => 'Generate Institutions Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'InstitutionProfiles.index|InstitutionProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'InstitutionProfiles.generate|InstitutionProfiles.downloadExcel|InstitutionProfiles.publish|InstitutionProfiles.unpublish|InstitutionProfiles.email|InstitutionProfiles.downloadAll|InstitutionProfiles.generateAll|InstitutionProfiles.publishAll|InstitutionProfiles.unpublishAll',
                'order' => $data[0] + 1,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Institutions Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'InstitutionProfiles.index|InstitutionProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'InstitutionProfiles.generate|InstitutionProfiles.downloadExcel|InstitutionProfiles.publish|InstitutionProfiles.unpublish|InstitutionProfiles.email|InstitutionProfiles.downloadAll|InstitutionProfiles.generateAll|InstitutionProfiles.publishAll|InstitutionProfiles.unpublishAll',
                'order' => $data[0] + 2,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Satff Profile',
                'controller' => 'Profiles',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'StaffProfiles.index|StaffProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.generate|StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.generateAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll',
                'order' => $data[0] + 3,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'name' => 'Download Satff Profile',
                'controller' => 'Profiles',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'StaffProfiles.index|StaffProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.generate|StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.generateAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll',
                'order' => $data[0] + 4,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Students Profile',
                'controller' => 'Profiles',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'StudentProfiles.index|StudentProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.generate|StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.generateAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
                'order' => $data[0] + 5,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Students Profile',
                'controller' => 'Profiles',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => 'StudentProfiles.index|StudentProfiles.view',
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.generate|StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.generateAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
                'order' => $data[0] + 6,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('security_functions', $data);
    }

    // Rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6286_security_functions` TO `security_functions`');
    }
}
