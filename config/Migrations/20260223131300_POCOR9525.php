<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Utility\Text;
use Cake\Log\Log;

class POCOR9525 extends AbstractMigration
{
    private const LABELS_TABLE = 'labels';
    private const BACKUP_LABELS_TABLE = 'z_9525_labels';

    /**
     * POCOR-9525
     * Owner: Rishabh 🦖
     */
    public function up(): void
    {

        $this->backupTables();
        $this->insertNewLabels();
    }

    public function down(): void
    {
        $this->restoreTables();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable(self::LABELS_TABLE, self::BACKUP_LABELS_TABLE);
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->restoreTable(self::LABELS_TABLE, self::BACKUP_LABELS_TABLE);
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info("Restored label table from backup");
    }

    private function backupTable(string $original, string $backup): void
    {
        if ($this->hasTable($backup)) {
            Log::warning("Backup table `$backup` already exists. Skipping backup.");
            return;
        }

        try {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
            $this->execute("
    UPDATE `$original`
    SET `modified` = NOW()
    WHERE `modified` LIKE '%0000%'
");
            Log::info("Normalized invalid modified timestamps in `$original`");
            $this->execute("
    UPDATE `$original`
    SET `created` = NOW()
    WHERE `created`  LIKE '%0000%'
");

            Log::info("Normalized invalid created timestamps in `$original`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");
            Log::info("Backed up `$original` to `$backup`");

        } catch (\Throwable $e) {
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE `$backup`");
                Log::warning("Backup failed; dropped incomplete backup table `$backup`");
            }

            throw $e;
        }
    }

    private function restoreTable(string $original, string $backup): void
    {
        if ($this->hasTable($backup)) {
            $this->execute("DROP TABLE IF EXISTS `$original`");
            $this->execute("RENAME TABLE `$backup` TO `$original`");
            Log::info("Restored `$original` from `$backup`");
        }
    }

    private function insertNewLabels(): void
    {
        $now = date('Y-m-d H:i:s');

        $labels = [
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionStaffFinanceSalaries',
                'field' => 'gross_salary',
                'module_name' => 'Institutions -> Staff -> Finance -> Salaries',
                'field_name' => 'Gross Salary',
                'code' => null,
                'name' => null,
                'visible' => 1,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => $now,
            ],
            [
                'id' => Text::uuid(),
                'module' => 'InstitutionStaffFinanceSalaries',
                'field' => 'net_salary',
                'module_name' => 'Institutions -> Staff -> Finance -> Salaries',
                'field_name' => 'Net Salary',
                'code' => null,
                'name' => null,
                'visible' => 1,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '1',
                'created' => $now,
            ]
        ];

        $this->table(self::LABELS_TABLE)->insert($labels)->saveData();
        Log::info("Inserted labels for Gross Salary and Net Salary in module `InstitutionStaffFinanceSalaries`");
    }
}
