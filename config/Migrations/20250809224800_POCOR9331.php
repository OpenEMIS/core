<?php

use Phinx\Migration\AbstractMigration;
use Cake\Utility\Inflector;

class POCOR9331 extends AbstractMigration
{
    public function up()
    {
        $api_tables = ["department_staff",
            "institution_departments",
            "institution_infrastructure_attachments",
            "infrastructure_attachment_types",
            "staff_leave_entitlements",
            "staff_leave_policies",
            "staff_leave_policy_types"];
        // --- Backup: Create backup table if it doesn't exist ---
        if (!$this->hasTable('z_9331_security_functions')) {
            $this->execute('CREATE TABLE `z_9331_security_functions` LIKE `security_functions`');
            $this->execute('INSERT INTO `z_9331_security_functions` SELECT * FROM `security_functions`');
        }

        // --- Update security_functions table based on new module rules ---
        $maxOrder = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");
        $i = $maxOrder[0] + 1;
        natsort($api_tables);
        foreach ($api_tables as $table) {
            $humanName = Inflector::humanize(trim($table));
            $modelName = Inflector::camelize(trim($table));

            // Build the INSERT statement.
            $sql = "INSERT IGNORE INTO security_functions
                    (id, name, controller, module, category, parent_id, _view, _edit, _add, _delete, _execute, `order`, visible, description, modified_user_id, modified, created_user_id, created)
                    VALUES
                    (
                        NULL,
                        '{$humanName}',
                        'API',
                        'API',
                        'API',
                        10000,
                        '{$modelName}.view|{$modelName}.list',
                        '{$modelName}.edit',
                        '{$modelName}.add',
                        '{$modelName}.delete',
                        NULL,
                        {$i},
                        1,
                        NULL,
                        NULL,
                        NULL,
                        2,
                        '2025-08-09 00:01:04'
                    );";
            $this->execute($sql);
            $i++;
        }


    }

    public function down()
    {
        // --- Rollback: If the backup table exists, drop the current table and rename the backup ---

        if ($this->hasTable('z_9331_security_functions')) {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute('DROP TABLE IF EXISTS `security_functions`');
            $this->execute('RENAME TABLE `z_9331_security_functions` TO `security_functions`');
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
