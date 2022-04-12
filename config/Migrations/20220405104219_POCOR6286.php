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

        //getting parent_id value for Institutions module
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'General'");
        $parentId = $row['id'];

        //getting parent_id value for Directory module
        $dirRow = $this->fetchRow("SELECT min(`id`) FROM `security_functions` WHERE `module` = 'Directory'");
        $dirParentId = $dirRow[0]; 

        //getting parent_id value for Personal module
        $perRow = $this->fetchRow("SELECT min(`id`) FROM `security_functions` WHERE `module` = 'Personal'");
        $perParentId = $perRow[0];

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
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'InstitutionProfiles.view|InstitutionProfiles.generate|InstitutionProfiles.generateAll',
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
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'InstitutionProfiles.downloadExcel|InstitutionProfiles.publish|InstitutionProfiles.unpublish|InstitutionProfiles.email|InstitutionProfiles.downloadAll|InstitutionProfiles.publishAll|InstitutionProfiles.unpublishAll',
                'order' => $data[0] + 2,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Staff Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.view|StaffProfiles.generate|StaffProfiles.generateAll',
                'order' => $data[0] + 3,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'name' => 'Download Staff Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll',
                'order' => $data[0] + 4,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Students Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll',
                'order' => $data[0] + 5,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Students Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
                'order' => $data[0] + 6,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Staff Profile',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Profiles',
                'parent_id' => $dirParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.view|StaffProfiles.generate|StaffProfiles.generateAll',
                'order' => $data[0] + 7,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'name' => 'Download Staff Profile',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Profiles',
                'parent_id' => $dirParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll',
                'order' => $data[0] + 8,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Students Profile',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Profiles',
                'parent_id' => $dirParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll',
                'order' => $data[0] + 9,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Students Profile',
                'controller' => 'Directories',
                'module' => 'Directory',
                'category' => 'Profiles',
                'parent_id' => $dirParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
                'order' => $data[0] + 10,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Staff Profile',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Profiles',
                'parent_id' => $perParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.view|StaffProfiles.generate|StaffProfiles.generateAll',
                'order' => $data[0] + 11,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ], [
                'name' => 'Download Staff Profile',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Profiles',
                'parent_id' => $perParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StaffProfiles.downloadExcel|StaffProfiles.publish|StaffProfiles.unpublish|StaffProfiles.email|StaffProfiles.downloadAll|StaffProfiles.publishAll|StaffProfiles.unpublishAll',
                'order' => $data[0] + 12,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Generate Students Profile',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Profiles',
                'parent_id' => $perParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.view|StudentProfiles.generate|StudentProfiles.generateAll',
                'order' => $data[0] + 13,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Students Profile',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' => 'Profiles',
                'parent_id' => $perParentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'StudentProfiles.downloadExcel|StudentProfiles.publish|StudentProfiles.unpublish|StudentProfiles.email|StudentProfiles.downloadAll|StudentProfiles.publishAll|StudentProfiles.unpublishAll',
                'order' => $data[0] + 14,
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
