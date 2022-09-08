<?php
use Migrations\AbstractMigration;

class POCOR6966 extends AbstractMigration
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
        $this->execute('CREATE TABLE `zz_6966_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6966_security_functions` SELECT * FROM `security_functions`');

         //getting parent_id value for Institutions module
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Institutions' AND `name` = 'Institution' AND `category` = 'General'");
        $parentId = $row['id'];

         //getting max order value
        $data = $this->fetchRow("SELECT  * FROM `security_functions` WHERE `name` = 'Download Students Profile' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Profiles'");
        
        //inserting record
        $data = [
            [   
                'name' => 'Generate Classes Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'ClassesProfiles.index|ClassesProfiles.view|ClassesProfiles.generate|ClassesProfiles.generateAll',
                'order' => $data['order'] + 1,
                'visible' => 1,
                'description' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],[
                'name' => 'Download Classes Profile',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Profiles',
                'parent_id' => $parentId,
                '_view' => NULL,
                '_edit' => NULL,
                '_add' => NULL,
                '_delete' => NULL,
                '_execute' => 'ClassesProfiles.view|ClassesProfiles.index', NULL, NULL, NULL, 'ClassesProfiles.downloadExcel|ClassesProfiles.publish|ClassesProfiles.unpublish|ClassesProfiles.email|ClassesProfiles.downloadAll|ClassesProfiles.publishAll|ClassesProfiles.unpublishAll|ClassesProfiles.index|ClassesProfiles.view',
                'order' => $data['order'] + 2,
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
        $this->execute('RENAME TABLE `zz_6966_security_functions` TO `security_functions`');
    }
}
