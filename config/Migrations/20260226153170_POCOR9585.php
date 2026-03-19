<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9585 extends AbstractMigration
{
    public function up(): void
    {
        // Backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9585_security_functions`');
        $this->execute('CREATE TABLE `zz_9585_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9585_security_functions` SELECT * FROM `security_functions`');

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Download Institution Profile PDF', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Profiles',  'parent_id' => $parentId,'_view' => 'InstitutionProfiles.index|InstitutionProfiles.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'InstitutionProfiles.download', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('security_functions')->insert($record)->save();

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Download Staff Profile PDF', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Profiles',  'parent_id' => $parentId,'_view' => 'StaffProfiles.index|StaffProfiles.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'StaffProfiles.download', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('security_functions')->insert($record)->save();

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Download Student Profile PDF', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Profiles',  'parent_id' => $parentId,'_view' => 'StudentProfiles.index|StudentProfiles.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'StudentProfiles.download', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('security_functions')->insert($record)->save();

        $row = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Profiles'");
        $parentId = $parent_id[0];
        $order = $row[0] + 1;
        $record = [
            [
                'name' => 'Download Classes Profile PDF', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Profiles',  'parent_id' => $parentId,'_view' => 'ClassesProfiles.index|ClassesProfiles.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => 'ClassesProfiles.download', 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('security_functions')->insert($record)->save();
        
    }

    public function down(): void
    {
        // Restore from backup
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_9585_security_functions` TO `security_functions`');

    }
}
