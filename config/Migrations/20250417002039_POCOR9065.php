<?php

declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9065 extends AbstractMigration
{
    private const TABLE = 'labels';
    private const BACKUP = 'z_9065_labels';

    public function up(): void
    {
        $this->backupTable();
        $this->insertLabelData();
    }

    public function down(): void
    {
        $this->restoreBackup();
    }

    /**
     * Backup the original labels table.
     */
    private function backupTable(): void
    {
        $this->execute(sprintf(
            'CREATE TABLE `%s` LIKE `%s`',
            self::BACKUP,
            self::TABLE
        ));
        $this->execute(sprintf(
            'INSERT INTO `%s` SELECT * FROM `%s`',
            self::BACKUP,
            self::TABLE
        ));
    }

    /**
     * Insert label data related to Outcome -> Criterias.
     */
    private function insertLabelData(): void
    {
        $this->execute("
            INSERT INTO `" . self::TABLE . "` (
                id, module, field, module_name, field_name,
                code, name, visible, created_user_id, created
            ) VALUES (
                uuid(), 'InstitutionFees', 'education_grade_id', 'Institutions > Finance > Institution Fees', 'Education Grade',
                NULL, NULL, 1, 1, NOW()
            )
        ");
    }

    /**
     * Restore the original table from backup.
     */
    private function restoreBackup(): void
    {
        $this->execute(sprintf('DROP TABLE IF EXISTS `%s`', self::TABLE));
        $this->execute(sprintf('RENAME TABLE `%s` TO `%s`', self::BACKUP, self::TABLE));
    }
}
