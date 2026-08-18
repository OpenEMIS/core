<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

// POCOR-9999
// Fixes stale audit-field label overrides stored in the `labels` table (and, indirectly,
// the file cache built from them) so that "Modified By"/"Modified On"/"Created By"/"Created On"
// become "Modified User"/"Modified"/"Created User"/"Created" everywhere the Labels admin
// screen (or a cached copy of it) is the source of the text, matching the code-level default
// wording used across the app.
//
// Both `name` (the explicit override) and `field_name` (the default English label stored on
// the row) are checked, because LabelsTable::concatenateLabel() falls back to `field_name`
// whenever `name` is empty, and that fallback value is what actually gets written into the
// `labels` file cache the first time each key is resolved.
//
// NOTE: this migration only fixes the underlying data. The `labels` cache config
// (config/app.php, File cache, 1 month TTL) is not touched here — clear
// tmp/cache/labels/ (or run your usual cache-clear step) after deploying so any
// already-cached old wording is dropped too.
class POCOR9999 extends AbstractMigration
{

    private const REPLACEMENTS = [
        'modified_user_id' => ['Modified By', 'Modified User'],
        'modified'         => ['Modified On', 'Modified'],
        'created_user_id'  => ['Created By', 'Created User'],
        'created'          => ['Created On', 'Created'],
    ];

    public function up(): void
    {
        $this->backupAffectedRows();

        foreach (self::REPLACEMENTS as $field => $pair) {
            [$old, $new] = $pair;
            $this->execute(
                "UPDATE labels
                    SET name = CASE WHEN name = '{$old}' THEN '{$new}' ELSE name END,
                        field_name = CASE WHEN field_name = '{$old}' THEN '{$new}' ELSE field_name END
                  WHERE field = '{$field}'
                    AND (name = '{$old}' OR field_name = '{$old}')"
            );
        }
    }

    public function down(): void
    {
        $this->restoreAffectedRows();
    }

    // -------------------------------------------------------------------------
    // Backup / Restore (scoped to just the rows this migration touches)
    // -------------------------------------------------------------------------

    private function backupTableName(): string
    {
        return 'z_' . self::TICKET . '_labels';
    }

    private function backupAffectedRows(): void
    {
        $backup = $this->backupTableName();

        if ($this->hasTable($backup)) {
            return;
        }

        $this->execute("CREATE TABLE `{$backup}` LIKE `labels`");

        $conditions = [];
        foreach (self::REPLACEMENTS as $field => $pair) {
            $old = $pair[0];
            $conditions[] = "(field = '{$field}' AND (name = '{$old}' OR field_name = '{$old}'))";
        }

        $this->execute(
            "INSERT INTO `{$backup}` SELECT * FROM `labels` WHERE " . implode(' OR ', $conditions)
        );
    }

    private function restoreAffectedRows(): void
    {
        $backup = $this->backupTableName();

        if (!$this->hasTable($backup)) {
            return;
        }

        $rows = $this->fetchAll("SELECT id, name, field_name FROM `{$backup}`");
        foreach ($rows as $row) {
            $id = $row['id'];
            $name = $row['name'];
            $fieldName = $row['field_name'];

            $nameSql = $name === null ? 'NULL' : "'" . addslashes($name) . "'";
            $fieldNameSql = $fieldName === null ? 'NULL' : "'" . addslashes($fieldName) . "'";

            $this->execute(
                "UPDATE labels SET name = {$nameSql}, field_name = {$fieldNameSql} WHERE id = '{$id}'"
            );
        }

        $this->execute("DROP TABLE IF EXISTS `{$backup}`");
    }
}
