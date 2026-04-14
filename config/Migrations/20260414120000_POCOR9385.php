<?php
use Migrations\AbstractMigration;

class POCOR9385 extends AbstractMigration
{
    public function up(): void
    {
        $this->backupTables();

        // Create student_creation_rules table //POCOR-9385: new table for per-grade creation rules
        $table = $this->table('student_creation_rules', ['id' => false, 'primary_key' => ['education_grade_id']]);
        $table->addColumn('education_grade_id', 'integer', ['null' => false])
              ->addColumn('allow_student_creation', 'boolean', ['null' => false, 'default' => true])
              ->addColumn('modified_user_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('created_user_id', 'integer', ['null' => false])
              ->addColumn('created', 'datetime', ['null' => false])
              ->create();

        // Seed one row per education_grade — all allowed by default //POCOR-9385: seed all grades as allowed
        $now = date('Y-m-d H:i:s');
        $grades = $this->fetchAll('SELECT id FROM education_grades ORDER BY id');
        $rows = [];
        foreach ($grades as $grade) {
            $rows[] = [
                'education_grade_id' => $grade['id'],
                'allow_student_creation' => 1,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => $now,
            ];
        }
        if (!empty($rows)) {
            $this->table('student_creation_rules')->insert($rows)->saveData();
        }

        // Insert config_items rows //POCOR-9385: toggle + excluded roles config items
        $this->execute("
            INSERT INTO `config_items`
                (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
                (1357, 'Limit student addition to first grade only', 'restrict_student_creation', 'Add New Student', 'Restrict Student Creation', '0', '', '0', 1, 1, 'Dropdown', 'student_creation_toggle', NULL, NULL, 1, NOW()),
                (1358, 'Excluded Security Roles for Student Creation', 'student_creation_excluded_roles', 'Add New Student', 'Excluded Security Roles', '', '', '', 1, 1, 'Dropdown', '', NULL, NULL, 1, NOW())
        ");

        // Insert config_item_options for the toggle //POCOR-9385: Enabled/Disabled options
        $this->execute("
            INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`)
            VALUES
                ('student_creation_toggle', 'Disabled', '0', 1, 1),
                ('student_creation_toggle', 'Enabled', '1', 2, 1)
        ");
    }

    public function down(): void
    {
        $this->restoreTable(); //POCOR-9385: restore config backups
        if ($this->hasTable('student_creation_rules')) {
            $this->table('student_creation_rules')->drop()->save(); //POCOR-9385: drop new table
        }
    }

    private function backupTables(): void //POCOR-9385: backup before changes
    {
        $tables = ['config_items', 'config_item_options'];
        foreach ($tables as $t) {
            $b = 'z_9385_' . $t;
            if (!$this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `{$b}` LIKE `{$t}`");
                $this->execute("INSERT INTO `{$b}` SELECT * FROM `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTable(): void //POCOR-9385: restore from backup
    {
        $tables = ['config_items', 'config_item_options'];
        foreach ($tables as $t) {
            $b = 'z_9385_' . $t;
            if ($this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$t}`");
                $this->execute("RENAME TABLE `{$b}` TO `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
