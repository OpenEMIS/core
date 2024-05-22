<?php

use Phinx\Migration\AbstractMigration;

/**
 * POCOR-7562
 * unique code validation
 **/
class POCOR7562 extends AbstractMigration
{
    // commit
    public function up()
    {
        // Backup table
        try {
            $this->execute('CREATE TABLE `z_7562_security_functions` LIKE `security_functions`');

            $this->execute('INSERT INTO `z_7562_security_functions` SELECT * FROM `security_functions`');

            // security_functions for Survey Filters
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 287');

//insert
            $record = [
                [
                    'name' => 'Filters', 'controller' => 'Surveys', 'module' => 'Administration', 'category' => 'Survey', 'parent_id' => 5000, '_view' => 'SurveyFilters.index|SurveyFilters.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 287, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ]
            ];
            $this->insert('security_functions', $record);

// security_functions for Survey Filters
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 288');
//insert
            $record = [
                [
                    'name' => 'Recipients', 'controller' => 'Surveys', 'module' => 'Administration', 'category' => 'Survey', 'parent_id' => 5000, '_view' => 'SurveyRecipients.index|SurveyRecipients.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 288, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ]
            ];
            $this->insert('security_functions', $record);

// security_functions for Survey Filters
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 410');
//insert
            $record = [
                [
                    'name' => 'Copy', 'controller' => 'Archives', 'module' => 'Administration', 'category' => 'Archive', 'parent_id' => 2000, '_view' => 'DataManagementCopy.index|DataManagementCopy.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 410, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ]
            ];
            $this->insert('security_functions', $record);

// for data management
            $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 411');
//insert
            $record = [
                [
                    'name' => 'Backup', 'controller' => 'Archives', 'module' => 'Administration', 'category' => 'Archive', 'parent_id' => 2000, '_view' => 'BackupLogs.index|BackupLogs.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 411, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
                ]
            ];
            $this->insert('security_functions', $record);

            $this->execute("UPDATE security_functions SET `_view` = 'Notices.index|Notices.view' WHERE `name` = 'Notices'");
            $this->execute("UPDATE security_functions SET `_view` = 'TransferLogs.index|TransferLogs.view' WHERE `name` = 'Archive'");

        } catch (\Exception $e) {

        }
    }

// rollback
    public
    function down()
    {
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7562_security_functions` TO `security_functions`');
    }
}


?>
